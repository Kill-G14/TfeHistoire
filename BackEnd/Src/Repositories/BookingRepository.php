<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Utils\Database;
use PDO;

class BookingRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer une réservation par ID
  public function getBookingById(int $id): ?Booking {
    $query = "SELECT * FROM bookings WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Booking::class);
    $booking = $stmt->fetch();
    return $booking ?: null;
  }

  // Récupérer les réservations par utilisateur
  public function getBookingsByUserId(int $userId): array {
    $query = "SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Booking::class);
    return $stmt->fetchAll();
  }

  // Récupérer les réservations par événement
  public function getBookingsByEventId(int $eventId): array {
    $query = "SELECT * FROM bookings WHERE event_id = :event_id ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Booking::class);
    return $stmt->fetchAll();
  }

  // Créer une réservation
  public function createBooking(Booking $booking): ?int {
    $query = "INSERT INTO bookings (user_id, event_id, tickets_count, total_price, booking_status, created_at, updated_at)
              VALUES (:user_id, :event_id, :tickets_count, :total_price, :booking_status, NOW(), NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $booking->user_id, PDO::PARAM_INT);
    $stmt->bindParam(':event_id', $booking->event_id, PDO::PARAM_INT);
    $stmt->bindParam(':tickets_count', $booking->tickets_count, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $booking->total_price);
    $stmt->bindParam(':booking_status', $booking->booking_status);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Mettre à jour le statut d'une réservation
  public function updateBookingStatus(int $id, string $status): bool {
    $query = "UPDATE bookings SET booking_status = :status, updated_at = NOW()
              WHERE id = :id";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':status', $status);
    
    return $stmt->execute();
  }

  // Annuler une réservation
  public function cancelBooking(int $id): bool {
    return $this->updateBookingStatus($id, 'cancelled');
  }

  // Vérifier si un utilisateur a déjà réservé un événement
  public function hasUserBookedEvent(int $userId, int $eventId): bool {
    $query = "SELECT COUNT(*) as count FROM bookings 
              WHERE user_id = :user_id AND event_id = :event_id 
              AND booking_status != 'cancelled'";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
  }
}
