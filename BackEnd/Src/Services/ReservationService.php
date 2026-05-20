<?php

namespace App\Services;

use App\Models\Reservation;
use App\Repositories\ReservationRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;

class ReservationService
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

        // Vérifier qu'il reste des places disponibles
        $reservedCount = $this->reservationRepository->getReservedCount($eventId);
        $availableTickets = $event->ticket_quantity - $reservedCount;

        if ($availableTickets < $quantity) {
            return [
                'success' => false,
                'message' => 'Plus assez de places disponibles'
            ];
        }

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->user_id = $userId;
        $reservation->event_id = $eventId;
        $reservation->quantity = $quantity;
        $reservation->status = 'confirmed';

        $createdReservation = $this->reservationRepository->create($reservation);

        if (!$createdReservation) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation'
            ];
        }

        return [
            'success' => true,
            'message' => 'Réservation effectuée avec succès',
            'data' => [
                'reservation_id' => $createdReservation->id,
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

        return [
            'success' => true,
            'data' => $reservations
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

        $cancelled = $this->reservationRepository->cancel($reservationId);

        if (!$cancelled) {
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation'
            ];
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
        $available = $event->ticket_quantity - $reservedCount;

        return [
            'success' => true,
            'data' => [
                'total' => $event->ticket_quantity,
                'reserved' => $reservedCount,
                'available' => max(0, $available)
            ]
        ];
    }
}
