<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Utils\Database;
use PDO;

class ReservationRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Créer une nouvelle réservation
     */
    public function create(Reservation $reservation): ?Reservation
    {
        $query = "INSERT INTO reservations (user_id, event_id, quantity, status, created_at)
                  VALUES (:user_id, :event_id, :quantity, :status, NOW())";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $reservation->user_id);
        $stmt->bindParam(':event_id', $reservation->event_id);
        $stmt->bindParam(':quantity', $reservation->quantity);
        $stmt->bindParam(':status', $reservation->status);

        if ($stmt->execute()) {
            $reservation->id = (int) $this->getPdo()->lastInsertId();
            return $this->getById($reservation->id);
        }

        return null;
    }

    /**
     * Récupérer une réservation par ID
     */
    public function getById(int $id): ?Reservation
    {
        $query = "SELECT * FROM reservations WHERE id = :id AND is_deleted = FALSE";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Reservation::class);
        $reservation = $stmt->fetch();
        return $reservation ?: null;
    }

    /**
     * Récupérer toutes les réservations d'un utilisateur
     */
    public function getByUserId(int $userId): array
    {
        $query = "SELECT reservation.*, 
                         event.title as event_title,
                         event.date as event_date,
                         event.time as event_time,
                         event.city as event_city,
                         event.address as event_address,
                         event.image_event as event_image,
                         event.is_free as event_is_free,
                         event.ticket_price as event_ticket_price
                  FROM reservations reservation
                  INNER JOIN events event ON reservation.event_id = event.id
                  WHERE reservation.user_id = :user_id 
                    AND reservation.is_deleted = FALSE
                    AND event.is_deleted = FALSE
                  ORDER BY reservation.created_at DESC";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifier si un utilisateur a déjà réservé pour un événement
     */
    public function hasReservation(int $userId, int $eventId): bool
    {
        $query = "SELECT COUNT(*) as count 
                  FROM reservations 
                  WHERE user_id = :user_id 
                    AND event_id = :event_id 
                    AND status = 'confirmed'
                    AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Récupérer le nombre de places réservées pour un événement
     */
    public function getReservedCount(int $eventId): int
    {
        $query = "SELECT COALESCE(SUM(quantity), 0) as total
                  FROM reservations 
                  WHERE event_id = :event_id 
                    AND status = 'confirmed'
                    AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Annuler une réservation
     */
    public function cancel(int $id): bool
    {
        $query = "UPDATE reservations 
                  SET status = 'cancelled', updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Supprimer (soft delete) une réservation
     */
    public function delete(int $id): bool
    {
        $query = "UPDATE reservations 
                  SET is_deleted = TRUE, updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
