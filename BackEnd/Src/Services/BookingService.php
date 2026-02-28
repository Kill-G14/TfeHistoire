<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ModelsDTO\BookingDTO;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;
use App\Validators\BookingValidator;
use App\Utils\Logger;

class BookingService {
  private BookingRepository $bookingRepository;
  private EventRepository $eventRepository;
  private BookingValidator $bookingValidator;

  public function __construct(BookingRepository $bookingRepository, EventRepository $eventRepository, BookingValidator $bookingValidator) {
    $this->bookingRepository = $bookingRepository;
    $this->eventRepository = $eventRepository;
    $this->bookingValidator = $bookingValidator;
  }

  // Récupérer les réservations d'un utilisateur
  public function getUserBookings(int $userId): array {
    $bookings = $this->bookingRepository->getBookingsByUserId($userId);
    
    $bookingDTOs = array_map(function($booking) {
      return (new BookingDTO($booking))->toArray();
    }, $bookings);

    return [
      'success' => true,
      'data' => $bookingDTOs
    ];
  }

  // Créer une réservation
  public function createBooking(array $data, int $userId): array {
    // Validation
    $errors = $this->bookingValidator->validate($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    $eventId = (int) $data['event_id'];
    $ticketsCount = (int) $data['tickets_count'];

    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($eventId);
    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier qu'il y a assez de tickets disponibles
    if ($event->available_tickets < $ticketsCount) {
      return [
        'success' => false,
        'message' => 'Pas assez de tickets disponibles'
      ];
    }

    // Vérifier que l'utilisateur n'a pas déjà réservé cet événement
    if ($this->bookingRepository->hasUserBookedEvent($userId, $eventId)) {
      return [
        'success' => false,
        'message' => 'Vous avez déjà réservé cet événement'
      ];
    }

    // Calculer le prix total
    $totalPrice = $event->price * $ticketsCount;

    // Créer l'objet Booking
    $booking = new Booking();
    $booking->user_id = $userId;
    $booking->event_id = $eventId;
    $booking->tickets_count = $ticketsCount;
    $booking->total_price = $totalPrice;
    $booking->booking_status = 'confirmed';

    // Décrémenter le nombre de tickets disponibles
    $ticketsUpdated = $this->eventRepository->decrementAvailableTickets($eventId, $ticketsCount);
    
    if (!$ticketsUpdated) {
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des tickets disponibles'
      ];
    }

    // Créer la réservation
    $bookingId = $this->bookingRepository->createBooking($booking);

    if (!$bookingId) {
      Logger::error('Failed to create booking', ['user_id' => $userId, 'event_id' => $eventId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création de la réservation'
      ];
    }

    // Récupérer la réservation créée
    $createdBooking = $this->bookingRepository->getBookingById($bookingId);

    Logger::info('Booking created successfully', ['booking_id' => $bookingId]);

    return [
      'success' => true,
      'message' => 'Réservation créée avec succès',
      'data' => (new BookingDTO($createdBooking))->toArray()
    ];
  }

  // Annuler une réservation
  public function cancelBooking(int $id, int $userId): array {
    // Récupérer la réservation
    $booking = $this->bookingRepository->getBookingById($id);

    if (!$booking) {
      return [
        'success' => false,
        'message' => 'Réservation non trouvée'
      ];
    }

    // Vérifier que l'utilisateur est le propriétaire
    if ($booking->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à annuler cette réservation'
      ];
    }

    // Vérifier que la réservation n'est pas déjà annulée
    if ($booking->booking_status === 'cancelled') {
      return [
        'success' => false,
        'message' => 'Cette réservation est déjà annulée'
      ];
    }

    // Annuler la réservation
    $success = $this->bookingRepository->cancelBooking($id);

    if (!$success) {
      Logger::error('Failed to cancel booking', ['booking_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de l\'annulation de la réservation'
      ];
    }

    Logger::info('Booking cancelled successfully', ['booking_id' => $id]);

    return [
      'success' => true,
      'message' => 'Réservation annulée avec succès'
    ];
  }
}
