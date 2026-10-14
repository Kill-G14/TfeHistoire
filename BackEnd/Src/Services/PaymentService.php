<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\UserRepository;
use App\Utils\Logger;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Paiements Stripe Connect (comptes Standard + paiements directs).
 *
 * Principe : l'argent est encaissé directement sur le compte Stripe de l'organisateur.
 * MemoriaEventia ne perçoit rien (aucun application_fee_amount) et ne rembourse rien.
 */
class PaymentService
{
    private UserRepository $userRepository;
    private EventRepository $eventRepository;
    private ReservationRepository $reservationRepository;
    private EmailService $emailService;

    public function __construct(
        UserRepository $userRepository,
        EventRepository $eventRepository,
        ReservationRepository $reservationRepository,
        EmailService $emailService
    ) {
        $this->userRepository = $userRepository;
        $this->eventRepository = $eventRepository;
        $this->reservationRepository = $reservationRepository;
        $this->emailService = $emailService;
    }

    private function stripe(): StripeClient
    {
        $secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        if ($secretKey === '') {
            throw new \RuntimeException('STRIPE_SECRET_KEY manquante dans le .env');
        }
        return new StripeClient($secretKey);
    }

    private function frontendUrl(): string
    {
        return rtrim($_ENV['FRONTEND_URL'] ?? '', '/');
    }

    // ============================================
    // COMPTE STRIPE DE L'ORGANISATEUR
    // ============================================

    /**
     * Démarrer (ou reprendre) la connexion du compte Stripe d'un organisateur.
     * Retourne l'URL Stripe où il termine son inscription.
     */
    public function startOnboarding(int $userId): array
    {
        $user = $this->userRepository->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }

        try {
            $stripe = $this->stripe();
            $accountId = $user->stripe_account_id;

            // Un seul compte Stripe par organisateur : on le crée à la première connexion
            if (empty($accountId)) {
                $account = $stripe->accounts->create([
                    'type' => 'standard',
                    'email' => $user->email,
                ]);
                $accountId = $account->id;
                $this->userRepository->setStripeAccountId($userId, $accountId);
            }

            $link = $stripe->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => $this->frontendUrl() . '/profile?section=events&stripe=refresh',
                'return_url' => $this->frontendUrl() . '/profile?section=events&stripe=return',
                'type' => 'account_onboarding',
            ]);

            return ['success' => true, 'data' => ['url' => $link->url]];
        } catch (ApiErrorException $exception) {
            Logger::error('Stripe onboarding failed', [
                'user_id' => $userId,
                'error' => $exception->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Impossible de contacter Stripe. Réessayez dans un instant.'];
        } catch (\Throwable $exception) {
            Logger::error('Stripe onboarding error', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            return ['success' => false, 'message' => 'Le paiement en ligne n\'est pas disponible pour le moment'];
        }
    }

    /**
     * État du compte Stripe de l'organisateur (met à jour la base si l'inscription est terminée).
     * Ne renvoie jamais l'identifiant du compte.
     */
    public function getAccountStatus(int $userId): array
    {
        $user = $this->userRepository->getUserById($userId);
        if (!$user) {
            return ['success' => false, 'message' => 'Utilisateur non trouvé'];
        }

        if (empty($user->stripe_account_id)) {
            return ['success' => true, 'data' => ['has_account' => false, 'connected' => false]];
        }

        try {
            $account = $this->stripe()->accounts->retrieve($user->stripe_account_id);
            $ready = (bool) $account->charges_enabled && (bool) $account->details_submitted;

            if ($ready !== (bool) $user->stripe_onboarding_completed) {
                $this->userRepository->setStripeOnboardingCompleted($userId, $ready);
            }

            return ['success' => true, 'data' => ['has_account' => true, 'connected' => $ready]];
        } catch (\Throwable $exception) {
            Logger::error('Stripe account status failed', ['user_id' => $userId, 'error' => $exception->getMessage()]);
            // En cas d'erreur Stripe, on garde l'état connu en base
            return [
                'success' => true,
                'data' => ['has_account' => true, 'connected' => (bool) $user->stripe_onboarding_completed],
            ];
        }
    }

    // ============================================
    // PAIEMENT D'UNE RÉSERVATION
    // ============================================

    /**
     * Créer la session de paiement Stripe Checkout, au nom du compte de l'organisateur.
     * Le montant vient de la base (prix de l'événement x quantité), jamais du navigateur.
     */
    public function createCheckoutSession(int $userId, int $reservationId): array
    {
        $reservation = $this->reservationRepository->getById($reservationId);

        // Un utilisateur ne peut payer que sa propre réservation
        if (!$reservation || $reservation->user_id !== $userId) {
            return ['success' => false, 'message' => 'Réservation non trouvée'];
        }

        if ($reservation->status !== 'pending_payment' || $reservation->payment_status !== 'pending') {
            return ['success' => false, 'message' => 'Cette réservation n\'est plus en attente de paiement'];
        }

        // Délai de retenue dépassé : les places ont pu être reprises, il faut réserver à nouveau
        if (!$this->reservationRepository->isPendingWithinHold($reservation->id)) {
            $this->reservationRepository->markPaymentUnsuccessful($reservation->id, 'expired');
            return ['success' => false, 'message' => 'Le délai de paiement est dépassé, veuillez réserver à nouveau'];
        }

        $event = $this->eventRepository->getEventById($reservation->event_id);
        $organizer = $event ? $this->userRepository->getUserById($event->user_id) : null;
        $user = $this->userRepository->getUserById($userId);

        if (!$event || !$organizer || !$user || empty($organizer->stripe_account_id) || !$organizer->stripe_onboarding_completed) {
            return ['success' => false, 'message' => 'L\'organisateur ne peut pas encaisser de paiement pour le moment'];
        }

        $unitAmount = (int) round(((float) $event->price) * 100);
        if ($unitAmount < 50) {
            return ['success' => false, 'message' => 'Prix invalide pour un paiement en ligne'];
        }

        $metadata = [
            'reservation_id' => (string) $reservation->id,
            'user_id' => (string) $userId,
            'event_id' => (string) $event->id,
        ];

        try {
            // Paiement direct sur le compte de l'organisateur : option stripe_account.
            // Aucun application_fee_amount : la plateforme ne prélève rien.
            $session = $this->stripe()->checkout->sessions->create([
                'mode' => 'payment',
                'line_items' => [[
                    'quantity' => $reservation->quantity,
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $unitAmount,
                        'product_data' => ['name' => $event->title],
                    ],
                ]],
                'customer_email' => $user->email,
                'client_reference_id' => (string) $reservation->id,
                'metadata' => $metadata,
                'payment_intent_data' => ['metadata' => $metadata],
                'success_url' => $this->frontendUrl() . '/payment/success?reservation=' . $reservation->id,
                'cancel_url' => $this->frontendUrl() . '/payment/cancel?reservation=' . $reservation->id,
                // Les places sont retenues 30 minutes : la session expire avec la réservation
                'expires_at' => time() + (ReservationRepository::PENDING_PAYMENT_MINUTES * 60) + 30,
            ], ['stripe_account' => $organizer->stripe_account_id]);

            $this->reservationRepository->setStripeSessionId($reservation->id, $session->id);

            return ['success' => true, 'data' => ['url' => $session->url]];
        } catch (\Throwable $exception) {
            Logger::error('Stripe checkout session failed', [
                'reservation_id' => $reservationId,
                'error' => $exception->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Impossible de démarrer le paiement. Réessayez dans un instant.'];
        }
    }

    /**
     * Au retour de Stripe : vérifier directement auprès de Stripe si la réservation est payée.
     * Filet de sécurité si le webhook est en retard ; le navigateur ne décide jamais du résultat.
     */
    public function confirmReturn(int $userId, int $reservationId): array
    {
        $reservation = $this->reservationRepository->getById($reservationId);

        if (!$reservation || $reservation->user_id !== $userId) {
            return ['success' => false, 'message' => 'Réservation non trouvée'];
        }

        // Déjà confirmée (par le webhook, par exemple)
        if ($reservation->payment_status === 'paid') {
            return ['success' => true, 'data' => ['payment_status' => 'paid']];
        }

        if ($reservation->payment_status !== 'pending' || empty($reservation->stripe_session_id)) {
            return ['success' => true, 'data' => ['payment_status' => $reservation->payment_status]];
        }

        $event = $this->eventRepository->getEventById($reservation->event_id);
        $organizer = $event ? $this->userRepository->getUserById($event->user_id) : null;
        if (!$organizer || empty($organizer->stripe_account_id)) {
            return ['success' => true, 'data' => ['payment_status' => $reservation->payment_status]];
        }

        try {
            $session = $this->stripe()->checkout->sessions->retrieve(
                $reservation->stripe_session_id,
                [],
                ['stripe_account' => $organizer->stripe_account_id]
            );

            if ($session->payment_status === 'paid') {
                $this->finalizePaidReservation($reservation->id);
                return ['success' => true, 'data' => ['payment_status' => 'paid']];
            }
        } catch (\Throwable $exception) {
            Logger::error('Stripe session check failed', [
                'reservation_id' => $reservationId,
                'error' => $exception->getMessage(),
            ]);
        }

        return ['success' => true, 'data' => ['payment_status' => 'pending']];
    }

    /**
     * L'acheteur abandonne le paiement (retour via le bouton "annuler" de Stripe) :
     * on libère les places tout de suite et on invalide la session pour qu'elle ne puisse plus être payée.
     */
    public function abandonPayment(int $userId, int $reservationId): array
    {
        $reservation = $this->reservationRepository->getById($reservationId);

        if (!$reservation || $reservation->user_id !== $userId) {
            return ['success' => false, 'message' => 'Réservation non trouvée'];
        }

        if ($reservation->payment_status !== 'pending') {
            return ['success' => true, 'data' => ['payment_status' => $reservation->payment_status]];
        }

        $this->reservationRepository->markPaymentUnsuccessful($reservation->id, 'expired');

        if (!empty($reservation->stripe_session_id)) {
            $event = $this->eventRepository->getEventById($reservation->event_id);
            $organizer = $event ? $this->userRepository->getUserById($event->user_id) : null;

            if ($organizer && !empty($organizer->stripe_account_id)) {
                try {
                    $this->stripe()->checkout->sessions->expire(
                        $reservation->stripe_session_id,
                        [],
                        ['stripe_account' => $organizer->stripe_account_id]
                    );
                } catch (\Throwable $exception) {
                    // Pas bloquant : la session expire d'elle-même au bout de 30 minutes
                    Logger::warning('Stripe session expire failed', [
                        'reservation_id' => $reservationId,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return ['success' => true, 'data' => ['payment_status' => 'expired']];
    }

    // ============================================
    // WEBHOOK
    // ============================================

    /**
     * Traiter un événement Stripe déjà vérifié (signature contrôlée par l'API du webhook).
     */
    public function handleWebhookEvent(\Stripe\Event $event): void
    {
        // Seuls les événements de session Checkout nous intéressent
        $type = $event->type;
        if (strpos($type, 'checkout.session.') !== 0) {
            return;
        }

        $session = $event->data->object;
        $reservation = $this->reservationRepository->getByStripeSessionId($session->id);

        if (!$reservation) {
            Logger::warning('Stripe webhook: reservation not found', ['session_id' => $session->id]);
            return;
        }

        // L'événement doit venir du compte Stripe de l'organisateur de cet événement
        $eventRecord = $this->eventRepository->getEventById($reservation->event_id);
        $organizer = $eventRecord ? $this->userRepository->getUserById($eventRecord->user_id) : null;

        if (!$organizer || empty($organizer->stripe_account_id) || ($event->account ?? null) !== $organizer->stripe_account_id) {
            Logger::warning('Stripe webhook: account mismatch', [
                'reservation_id' => $reservation->id,
                'event_account' => $event->account ?? null,
            ]);
            return;
        }

        switch ($type) {
            case 'checkout.session.completed':
                if (($session->payment_status ?? '') === 'paid') {
                    $this->finalizePaidReservation($reservation->id);
                }
                break;

            case 'checkout.session.async_payment_succeeded':
                $this->finalizePaidReservation($reservation->id);
                break;

            case 'checkout.session.expired':
                $this->reservationRepository->markPaymentUnsuccessful($reservation->id, 'expired');
                break;

            case 'checkout.session.async_payment_failed':
                $this->reservationRepository->markPaymentUnsuccessful($reservation->id, 'failed');
                break;
        }
    }

    /**
     * Confirmer une réservation payée et prévenir l'acheteur, une seule fois.
     */
    private function finalizePaidReservation(int $reservationId): void
    {
        // markPaid renvoie false si le paiement était déjà enregistré : pas de deuxième e-mail
        if (!$this->reservationRepository->markPaid($reservationId)) {
            return;
        }

        $reservation = $this->reservationRepository->getById($reservationId);
        if (!$reservation) {
            return;
        }

        Logger::info('Reservation paid', ['reservation_id' => $reservationId]);

        $user = $this->userRepository->getUserById($reservation->user_id);
        $event = $this->eventRepository->getEventById($reservation->event_id);

        if ($user && $event) {
            $organizer = $this->userRepository->getUserById($event->user_id);

            $this->emailService->sendReservationConfirmation(
                $user->email,
                $user->name,
                [
                    'title' => $event->title,
                    'date' => $event->date,
                    'time' => $event->time,
                    'address' => $event->address,
                    'city' => $event->city,
                    'country' => $event->country,
                ],
                $reservation->quantity,
                [
                    'reservation_id' => $reservation->id,
                    'amount_total' => $reservation->amount_total,
                    'organizer_name' => $organizer->name ?? null,
                    'organizer_email' => $organizer->email ?? null,
                ]
            );
        }
    }
}
