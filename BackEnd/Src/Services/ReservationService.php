<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ModelsDTO\ReservationDTO;
use App\Repositories\ReservationRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Utils\Logger;

class ReservationService
{
    // Plafond de places par commande (limite les abus et simplifie le paiement)
    public const MAX_QUANTITY_PER_ORDER = 10;

    private ReservationRepository $reservationRepository;
    private EventRepository $eventRepository;
    private UserRepository $userRepository;
    private EmailService $emailService;

    public function __construct(
        ReservationRepository $reservationRepository,
        EventRepository $eventRepository,
        UserRepository $userRepository,
        EmailService $emailService
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->eventRepository = $eventRepository;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
    }

    /**
     * Créer une nouvelle réservation
     */
    public function createReservation(int $userId, int $eventId, int $quantity = 1): array
    {
        // Vérifier que l'utilisateur existe
        $user = $this->userRepository->getUserById($userId);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ];
        }

        // Vérifier que l'événement existe et est approuvé
        $event = $this->eventRepository->getEventById($eventId);
        if (!$event) {
            return [
                'success' => false,
                'message' => 'Événement non trouvé'
            ];
        }

        if (!$event->is_approved) {
            return [
                'success' => false,
                'message' => 'Cet événement n\'est pas encore approuvé'
            ];
        }

        // Vérifier que l'utilisateur n'a pas déjà réservé
        if ($this->reservationRepository->hasReservation($userId, $eventId)) {
            return [
                'success' => false,
                'message' => 'Vous avez déjà réservé pour cet événement'
            ];
        }

        // Quantité : entiers de 1 à 10 par commande
        $quantity = (int) $quantity;
        if ($quantity < 1 || $quantity > self::MAX_QUANTITY_PER_ORDER) {
            return [
                'success' => false,
                'message' => 'Vous pouvez réserver entre 1 et ' . self::MAX_QUANTITY_PER_ORDER . ' places par commande'
            ];
        }

        // Événement payant : le prix vient de la base, jamais du navigateur
        $price = (float) $event->price;
        $isPaid = $price > 0;

        if ($isPaid) {
            $organizer = $this->userRepository->getUserById($event->user_id);
            if (!$organizer || !$organizer->stripe_onboarding_completed || empty($organizer->stripe_account_id)) {
                return [
                    'success' => false,
                    'message' => 'L\'organisateur ne peut pas encaisser de paiement pour le moment'
                ];
            }

            // Un paiement abandonné par le même utilisateur ne doit plus retenir de places
            $this->reservationRepository->expirePendingForUser($userId, $eventId);
        }

        // Créer la réservation : les places sont retenues pendant le paiement
        $reservation = new Reservation();
        $reservation->user_id = $userId;
        $reservation->event_id = $eventId;
        $reservation->quantity = $quantity;
        $reservation->status = $isPaid ? 'pending_payment' : 'confirmed';
        $reservation->payment_status = $isPaid ? 'pending' : 'not_required';
        $reservation->amount_total = $isPaid ? round($price * $quantity, 2) : null;

        try {
            // Contrôle des places et insertion dans une même transaction (pas de survente)
            // ticket_quantity = 0 signifie "illimité"
            $createdReservation = $this->reservationRepository->createWithSeatCheck(
                $reservation,
                (int) $event->ticket_quantity
            );
        } catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'no_seats') {
                return [
                    'success' => false,
                    'message' => 'Plus assez de places disponibles'
                ];
            }
            throw $exception;
        }

        if (!$createdReservation) {
            Logger::error('Failed to create reservation', [
                'user_id' => $userId,
                'event_id' => $eventId
            ]);
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation'
            ];
        }

        Logger::info('Reservation created successfully', [
            'reservation_id' => $createdReservation->id,
            'user_id' => $userId,
            'event_id' => $eventId,
            'quantity' => $quantity
        ]);

        // Événement payant : l'e-mail est envoyé par le webhook, une fois le paiement confirmé
        if (!$isPaid) {
            $eventData = [
                'title' => $event->title,
                'date' => $event->date,
                'time' => $event->time,
                'address' => $event->address,
                'city' => $event->city,
                'country' => $event->country
            ];

            Logger::info('Sending confirmation email', [
                'to' => $user->email,
                'event' => $event->title
            ]);

            $this->emailService->sendReservationConfirmation(
                $user->email,
                $user->name,
                $eventData,
                $quantity
            );
        }

        return [
            'success' => true,
            'message' => $isPaid ? 'Réservation créée, en attente de paiement' : 'Réservation effectuée avec succès',
            'data' => [
                'reservation_id' => $createdReservation->id,
                'requires_payment' => $isPaid,
                'amount_total' => $createdReservation->amount_total,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date' => $event->date,
                    'time' => $event->time
                ]
            ]
        ];
    }

    /**
     * Récupérer les réservations d'un utilisateur
     */
    public function getUserReservations(int $userId): array
    {
        $reservations = $this->reservationRepository->getByUserId($userId);

        // Transformer les réservations en DTOs
        $reservationDTOs = array_map(function($reservation) {
            return (new ReservationDTO($reservation))->toArray();
        }, $reservations);

        return [
            'success' => true,
            'data' => $reservationDTOs
        ];
    }

    /**
     * Annuler une réservation
     */
    public function cancelReservation(int $reservationId, int $userId): array
    {
        $reservation = $this->reservationRepository->getById($reservationId);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => 'Réservation non trouvée'
            ];
        }

        // Vérifier que la réservation appartient à l'utilisateur
        if ($reservation->user_id !== $userId) {
            return [
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à annuler cette réservation'
            ];
        }

        if ($reservation->status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Cette réservation est déjà annulée'
            ];
        }

        // Récupérer les informations de l'utilisateur et de l'événement pour l'email
        $user = $this->userRepository->getUserById($userId);
        $event = $this->eventRepository->getEventById($reservation->event_id);

        $cancelled = $this->reservationRepository->cancel($reservationId);

        if (!$cancelled) {
            Logger::error('Failed to cancel reservation', [
                'reservation_id' => $reservationId,
                'user_id' => $userId
            ]);
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation'
            ];
        }

        Logger::info('Reservation cancelled successfully', [
            'reservation_id' => $reservationId,
            'user_id' => $userId,
            'event_id' => $reservation->event_id
        ]);

        // Envoyer un email de confirmation d'annulation
        if ($user && $event) {
            $eventData = [
                'title' => $event->title,
                'date' => $event->date,
                'time' => $event->time,
                'address' => $event->address,
                'city' => $event->city,
                'country' => $event->country
            ];

            Logger::info('Sending cancellation email', [
                'to' => $user->email,
                'event' => $event->title
            ]);

            $this->emailService->sendReservationCancellation(
                $user->email,
                $user->name,
                $eventData
            );
        }

        return [
            'success' => true,
            'message' => 'Réservation annulée avec succès'
        ];
    }

    /**
     * Récupérer le nombre de places disponibles pour un événement
     */
    public function getAvailableTickets(int $eventId): array
    {
        $event = $this->eventRepository->getEventById($eventId);

        if (!$event) {
            return [
                'success' => false,
                'message' => 'Événement non trouvé'
            ];
        }

        $reservedCount = $this->reservationRepository->getReservedCount($eventId);

        // Si ticket_quantity = 0, cela signifie "illimité" (événement gratuit sans limite)
        if ($event->ticket_quantity === 0) {
            return [
                'success' => true,
                'data' => [
                    'total' => 0,
                    'reserved' => $reservedCount,
                    'available' => -1, // -1 signifie "illimité"
                    'unlimited' => true
                ]
            ];
        }

        $available = $event->ticket_quantity - $reservedCount;

        return [
            'success' => true,
            'data' => [
                'total' => $event->ticket_quantity,
                'reserved' => $reservedCount,
                'available' => max(0, $available),
                'unlimited' => false
            ]
        ];
    }
}
