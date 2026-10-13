<?php

namespace App\Repositories;

use App\Models\Reservation;
use App\Utils\Database;
use PDO;

class ReservationRepository
{
    private PDO $pdo;

    // Durée pendant laquelle des places restent retenues en attendant le paiement
    public const PENDING_PAYMENT_MINUTES = 30;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    private function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Condition SQL : réservations qui occupent des places
     * (confirmées, ou en attente de paiement depuis moins de PENDING_PAYMENT_MINUTES)
     */
    private function seatHoldingCondition(): string
    {
        return "(status = 'confirmed' OR (status = 'pending_payment'
                 AND created_at > (NOW() - INTERVAL " . self::PENDING_PAYMENT_MINUTES . " MINUTE)))";
    }

    /**
     * Créer une réservation en vérifiant les places dans une transaction.
     * Le verrou sur la ligne de l'événement évite la survente quand plusieurs
     * personnes réservent les dernières places en même temps.
     *
     * @param int $capacity Capacité de l'événement (0 = illimité)
     * @throws \RuntimeException avec le message "no_seats" si les places restantes sont insuffisantes
     */
    public function createWithSeatCheck(Reservation $reservation, int $capacity): ?Reservation
    {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare("SELECT id FROM events WHERE id = :id FOR UPDATE");
            $lock->bindParam(':id', $reservation->event_id);
            $lock->execute();

            if ($capacity > 0) {
                $remaining = $capacity - $this->getReservedCount($reservation->event_id);
                if ($remaining < $reservation->quantity) {
                    $pdo->rollBack();
                    throw new \RuntimeException('no_seats');
                }
            }

            $created = $this->create($reservation);
            $pdo->commit();
            return $created;
        } catch (\RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Créer une nouvelle réservation
     */
    public function create(Reservation $reservation): ?Reservation
    {
        $query = "INSERT INTO reservations (user_id, event_id, quantity, status, payment_status, amount_total, created_at)
                  VALUES (:user_id, :event_id, :quantity, :status, :payment_status, :amount_total, NOW())";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $reservation->user_id);
        $stmt->bindParam(':event_id', $reservation->event_id);
        $stmt->bindParam(':quantity', $reservation->quantity);
        $stmt->bindParam(':status', $reservation->status);
        $stmt->bindParam(':payment_status', $reservation->payment_status);
        $stmt->bindParam(':amount_total', $reservation->amount_total);

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
                         event.price as event_price,
                         organizer.name as organizer_name,
                         organizer.email as organizer_email
                  FROM reservations reservation
                  INNER JOIN events event ON reservation.event_id = event.id
                  INNER JOIN users organizer ON organizer.id = event.user_id
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
                    AND " . $this->seatHoldingCondition() . "
                    AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':event_id', $eventId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Libérer les places retenues par les paiements non terminés d'un utilisateur pour un événement
     */
    public function expirePendingForUser(int $userId, int $eventId): bool
    {
        $query = "UPDATE reservations
                  SET status = 'cancelled', payment_status = 'expired', updated_at = NOW()
                  WHERE user_id = :user_id AND event_id = :event_id
                    AND status = 'pending_payment' AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':event_id', $eventId);
        return $stmt->execute();
    }

    /**
     * La réservation est-elle encore dans le délai de retenue des places (paiement possible) ?
     */
    public function isPendingWithinHold(int $id): bool
    {
        $query = "SELECT COUNT(*) FROM reservations
                  WHERE id = :id AND status = 'pending_payment'
                    AND created_at > (NOW() - INTERVAL " . self::PENDING_PAYMENT_MINUTES . " MINUTE)";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Mémoriser la session de paiement Stripe d'une réservation
     */
    public function setStripeSessionId(int $id, string $sessionId): bool
    {
        $query = "UPDATE reservations SET stripe_session_id = :session_id, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Récupérer une réservation à partir de sa session Stripe
     */
    public function getByStripeSessionId(string $sessionId): ?Reservation
    {
        $query = "SELECT * FROM reservations WHERE stripe_session_id = :session_id AND is_deleted = FALSE";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':session_id', $sessionId);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Reservation::class);
        $reservation = $stmt->fetch();
        return $reservation ?: null;
    }

    /**
     * Confirmer le paiement d'une réservation.
     * Idempotent : ne fait rien (retourne false) si le paiement a déjà été enregistré,
     * ce qui évite un deuxième e-mail quand Stripe renvoie le même webhook.
     */
    public function markPaid(int $id): bool
    {
        $query = "UPDATE reservations
                  SET status = 'confirmed', payment_status = 'paid', paid_at = NOW(), updated_at = NOW()
                  WHERE id = :id AND payment_status IN ('pending', 'expired') AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Marquer un paiement comme échoué ou expiré et libérer les places
     *
     * @param string $paymentStatus 'failed' ou 'expired'
     */
    public function markPaymentUnsuccessful(int $id, string $paymentStatus): bool
    {
        $query = "UPDATE reservations
                  SET status = 'cancelled', payment_status = :payment_status, updated_at = NOW()
                  WHERE id = :id AND payment_status = 'pending' AND is_deleted = FALSE";

        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':payment_status', $paymentStatus);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Annuler une réservation
     */
    public function cancel(int $id): bool
    {
        $query = "UPDATE reservations 
                  SET status = 'cancelled',
                      payment_status = IF(payment_status = 'pending', 'expired', payment_status),
                      updated_at = NOW()
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
