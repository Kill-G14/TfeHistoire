<?php

namespace App\Repositories;

use App\Models\EventModification;
use App\Utils\Database;
use PDO;

class EventModificationRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Créer une demande de modification
  public function createModification(EventModification $modification): ?int {
    $query = "INSERT INTO event_modifications 
              (event_id, new_date, new_time, old_date, old_time, status, created_at)
              VALUES (:event_id, :new_date, :new_time, :old_date, :old_time, 'pending', NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $modification->event_id, PDO::PARAM_INT);
    $stmt->bindParam(':new_date', $modification->new_date);
    $stmt->bindParam(':new_time', $modification->new_time);
    $stmt->bindParam(':old_date', $modification->old_date);
    $stmt->bindParam(':old_time', $modification->old_time);
    
    $stmt->execute();
    $lastId = (int) $this->getPdo()->lastInsertId();
    return $lastId > 0 ? $lastId : null;
  }

  // Récupérer une modification par ID
  public function getModificationById(int $id): ?EventModification {
    $query = "SELECT * FROM event_modifications WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, EventModification::class);
    $modification = $stmt->fetch();
    return $modification ?: null;
  }

  // Récupérer les modifications en attente
  public function getPendingModifications(): array {
    $query = "SELECT em.*, e.title as event_title, e.user_id, u.name as user_name, u.email as user_email
              FROM event_modifications em
              INNER JOIN events e ON em.event_id = e.id
              INNER JOIN users u ON e.user_id = u.id
              WHERE em.status = 'pending'
              ORDER BY em.created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Récupérer la modification en attente pour un événement spécifique
  public function getPendingModificationByEventId(int $eventId): ?EventModification {
    $query = "SELECT * FROM event_modifications 
              WHERE event_id = :event_id AND status = 'pending'
              ORDER BY created_at DESC
              LIMIT 1";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, EventModification::class);
    $modification = $stmt->fetch();
    return $modification ?: null;
  }

  // Approuver une modification
  public function approveModification(int $id): bool {
    $query = "UPDATE event_modifications 
              SET status = 'approved', validated_at = NOW()
              WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Rejeter une modification
  public function rejectModification(int $id, string $reason): bool {
    $query = "UPDATE event_modifications 
              SET status = 'rejected', rejection_reason = :reason, validated_at = NOW()
              WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':reason', $reason);
    return $stmt->execute();
  }

  // Supprimer les modifications d'un événement
  public function deleteModificationsByEventId(int $eventId): bool {
    $query = "DELETE FROM event_modifications WHERE event_id = :event_id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    return $stmt->execute();
  }
}
