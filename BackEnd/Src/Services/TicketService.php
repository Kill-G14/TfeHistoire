<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Reservation;
use App\Repositories\EventRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\UserRepository;
use App\Utils\Logger;

class TicketService
{
    private ReservationRepository $reservationRepository;
    private EventRepository $eventRepository;
    private UserRepository $userRepository;

    public function __construct(
        ReservationRepository $reservationRepository,
        EventRepository $eventRepository,
        UserRepository $userRepository
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->eventRepository = $eventRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Billets d'une réservation (un par place) avec leur QR code, pour l'affichage du profil.
     */
    public function getTickets(int $userId, int $reservationId): array
    {
        $context = $this->loadTicketContext($userId, $reservationId);
        if (isset($context['error'])) {
            return ['success' => false, 'message' => $context['error'], 'status' => $context['status']];
        }

        /** @var Reservation $reservation */
        $reservation = $context['reservation'];

        $tickets = [];
        for ($seatNumber = 1; $seatNumber <= $reservation->quantity; $seatNumber++) {
            $code = $this->buildTicketCode($reservation->id, $seatNumber);
            $tickets[] = [
                'seat_number' => $seatNumber,
                'seat_total' => $reservation->quantity,
                'code' => $code,
                'qr_image' => $this->buildQrDataUri($code),
            ];
        }

        return [
            'success' => true,
            'data' => [
                'reservation_id' => $reservation->id,
                'reference' => $this->buildReference($reservation->id),
                'tickets' => $tickets,
            ],
        ];
    }

    /**
     * PDF prêt à être joint à l'e-mail de confirmation.
     * Retourne un tableau vide en cas d'échec : le mail part alors sans pièce jointe.
     */
    public function buildEmailAttachment(int $userId, int $reservationId): array
    {
        try {
            $result = $this->generatePdf($userId, $reservationId);
        } catch (\Throwable $exception) {
            Logger::error('Billet PDF non joint à l\'e-mail', ['error' => $exception->getMessage()]);
            return [];
        }

        if (!$result['success']) {
            Logger::warning('Billet PDF non joint à l\'e-mail', ['reason' => $result['message']]);
            return [];
        }

        return ['pdf_content' => $result['pdf'], 'pdf_filename' => $result['filename']];
    }

    /**
     * Vérifier un billet à partir du contenu du QR code (lien complet ou code seul).
     * Public volontairement (n'importe qui peut scanner) : ne renvoie que le strict nécessaire.
     */
    public function verifyTicket(string $scannedValue): array
    {
        // Le QR contient un lien : on en extrait seulement le code, au format strict
        if (!preg_match('/MEM-(\d{6})-(\d{1,3})-([0-9A-Fa-f]{12})/', $scannedValue, $matches)) {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Code de billet non reconnu']];
        }

        $reservationId = (int) $matches[1];
        $seatNumber = (int) $matches[2];

        // Comparaison à temps constant : la signature ne peut pas être devinée petit à petit
        $expectedCode = $this->buildTicketCode($reservationId, $seatNumber);
        if (!hash_equals($expectedCode, strtoupper($matches[0]))) {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Billet falsifié ou invalide']];
        }

        $reservation = $this->reservationRepository->getById($reservationId);
        if (!$reservation || $seatNumber < 1 || $seatNumber > $reservation->quantity) {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Billet introuvable']];
        }

        if ($reservation->status === 'cancelled') {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Réservation annulée']];
        }

        $isSettled = in_array($reservation->payment_status, ['not_required', 'paid'], true);
        if ($reservation->status !== 'confirmed' || !$isSettled) {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Réservation non confirmée ou non payée']];
        }

        $event = $this->eventRepository->getEventById($reservation->event_id);
        $holder = $this->userRepository->getUserById($reservation->user_id);
        if (!$event || !$holder) {
            return ['success' => true, 'data' => ['valid' => false, 'reason' => 'Événement introuvable']];
        }

        return [
            'success' => true,
            'data' => [
                'valid' => true,
                'reason' => 'Billet valide',
                'event_title' => $event->title,
                'event_date' => $event->date,
                'event_time' => substr((string) $event->time, 0, 5),
                'event_city' => $event->city,
                'organizer_name' => $event->organizer_name,
                'seat_number' => $seatNumber,
                'seat_total' => $reservation->quantity,
                'reference' => $this->buildReference($reservation->id),
                // Initiales seulement : le contrôleur reconnaît la personne sans exposer son identité
                'holder_initials' => $this->buildInitials($holder->name),
            ],
        ];
    }

    /**
     * PDF d'une réservation : une page par place.
     * Retourne ['success' => true, 'pdf' => contenu, 'filename' => nom] ou une erreur.
     */
    public function generatePdf(int $userId, int $reservationId): array
    {
        $context = $this->loadTicketContext($userId, $reservationId);
        if (isset($context['error'])) {
            return ['success' => false, 'message' => $context['error'], 'status' => $context['status']];
        }

        /** @var Reservation $reservation */
        $reservation = $context['reservation'];
        /** @var Event $event */
        $event = $context['event'];
        $holder = $context['holder'];
        $organizer = $context['organizer'];

        $pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetCreator('MemoriaEventia');
        $pdf->SetTitle('Billet - ' . $event->title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(false);

        $eventDate = date('d/m/Y', strtotime($event->date));
        $eventTime = substr((string) $event->time, 0, 5);
        $location = trim($event->address . ', ' . $event->city . ', ' . $event->country, ', ');
        $organizerName = $event->organizer_name ?? ($organizer->name ?? 'l\'organisateur');
        $organizerEmail = $organizer->email ?? '';
        $reference = $this->buildReference($reservation->id);
        $logoPath = dirname(__DIR__, 3) . '/assets/images/MemoriaEventiaLogo-480.png';

        for ($seatNumber = 1; $seatNumber <= $reservation->quantity; $seatNumber++) {
            $code = $this->buildTicketCode($reservation->id, $seatNumber);
            $pdf->AddPage();

            // Bandeau aux couleurs du site (vert principal)
            $pdf->SetFillColor(15, 94, 89);
            $pdf->Rect(0, 0, 148, 26, 'F');
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('helvetica', 'B', 20);
            $pdf->SetXY(10, 8);
            $pdf->Cell(80, 10, 'BILLET', 0, 0, 'L');
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetXY(80, 9);
            $pdf->Cell(58, 8, 'Place ' . $seatNumber . ' / ' . $reservation->quantity, 0, 0, 'R');

            // Organisateur en premier : c'est lui le vendeur
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY(10, 32);
            $pdf->Cell(128, 5, 'Billet émis par ' . $organizerName, 0, 1, 'L');

            // Titre de l'événement
            $pdf->SetTextColor(10, 63, 59);
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetXY(10, 40);
            $pdf->MultiCell(128, 8, $event->title, 0, 'L');

            // Informations pratiques
            $pdf->SetTextColor(31, 42, 42);
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetXY(10, $pdf->GetY() + 3);
            $pdf->MultiCell(128, 6, "Date : {$eventDate} à {$eventTime}\nLieu : {$location}\nTitulaire : {$holder->name}\nRéférence : {$reference}", 0, 'L');

            // QR code signé de la place
            $qrY = max($pdf->GetY() + 6, 92);
            $pdf->write2DBarcode($this->buildQrPayload($code), 'QRCODE,M', 49, $qrY, 50, 50, [
                'border' => false,
                'padding' => 0,
                'fgcolor' => [10, 63, 59],
                'bgcolor' => false,
            ], 'N');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(10, $qrY + 52);
            $pdf->Cell(128, 5, $code, 0, 1, 'C');

            // Mention de positionnement : la plateforme n'est pas partie à la vente
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetXY(10, 183);
            $contact = $organizerEmail !== '' ? ' Contact : ' . $organizerEmail . '.' : '';
            $pdf->MultiCell(
                128,
                4,
                "Billet émis par {$organizerName} via la plateforme MemoriaEventia, qui n'est pas partie au contrat de vente.{$contact} Pour toute demande de remboursement ou litige, contactez l'organisateur.",
                0,
                'C'
            );

            if (is_file($logoPath)) {
                $pdf->Image($logoPath, 59, 197, 30, 0, 'PNG');
            }
        }

        return [
            'success' => true,
            'pdf' => $pdf->Output('billet.pdf', 'S'),
            'filename' => 'billets-' . $reference . '.pdf',
        ];
    }

    /**
     * Charge la réservation, l'événement, le titulaire et l'organisateur,
     * et vérifie que l'utilisateur a bien le droit d'obtenir les billets.
     */
    private function loadTicketContext(int $userId, int $reservationId): array
    {
        $reservation = $this->reservationRepository->getById($reservationId);

        // Même message pour « inexistante » et « pas la sienne » : on ne révèle pas l'existence
        if (!$reservation || $reservation->user_id !== $userId) {
            return ['error' => 'Réservation introuvable', 'status' => 404];
        }

        $isConfirmed = $reservation->status === 'confirmed';
        $isSettled = in_array($reservation->payment_status, ['not_required', 'paid'], true);
        if (!$isConfirmed || !$isSettled) {
            return ['error' => 'Les billets ne sont disponibles que pour une réservation confirmée et payée', 'status' => 403];
        }

        $event = $this->eventRepository->getEventById($reservation->event_id);
        $holder = $this->userRepository->getUserById($userId);
        if (!$event || !$holder) {
            return ['error' => 'Événement introuvable', 'status' => 404];
        }

        $organizer = $this->userRepository->getUserById($event->user_id);

        return [
            'reservation' => $reservation,
            'event' => $event,
            'holder' => $holder,
            'organizer' => $organizer,
        ];
    }

    private function buildReference(int $reservationId): string
    {
        return 'MEM-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Code d'une place : référence + numéro + signature HMAC.
     * Sans la clé du .env, impossible de fabriquer un code valide.
     */
    private function buildTicketCode(int $reservationId, int $seatNumber): string
    {
        $secret = $_ENV['TICKET_SECRET'] ?? '';
        if ($secret === '') {
            Logger::error('TICKET_SECRET manquante dans le .env');
            throw new \RuntimeException('TICKET_SECRET manquante dans le .env');
        }

        $signature = substr(hash_hmac('sha256', $reservationId . ':' . $seatNumber, $secret), 0, 12);

        return $this->buildReference($reservationId) . '-' . $seatNumber . '-' . strtoupper($signature);
    }

    // Initiales d'un nom complet (ex. « Killian Godmont » devient « K. G. »)
    private function buildInitials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
        $initials = array_map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)) . '.', $parts);

        return implode(' ', $initials);
    }

    /**
     * Contenu du QR : le lien de la page de vérification (le téléphone l'ouvre directement).
     * Sans FRONTEND_URL, le QR contient le code seul.
     */
    private function buildQrPayload(string $code): string
    {
        $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? '', '/');

        return $frontendUrl !== ''
            ? $frontendUrl . '/ticket/verify?code=' . urlencode($code)
            : $code;
    }

    // QR code en SVG intégré (data URI) : aucune image stockée sur le serveur
    private function buildQrDataUri(string $code): string
    {
        $barcode = new \TCPDF2DBarcode($this->buildQrPayload($code), 'QRCODE,M');
        $svg = $barcode->getBarcodeSVGcode(4, 4, 'black');

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
