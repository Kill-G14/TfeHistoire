<?php

namespace App\Repositories;

use App\Models\TicketGenerated;
use App\Utils\Database;
use PDO;

class PurchasedTicketRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un billet généré par ID
  public function getTicketGeneratedById(int $id): ?TicketGenerated {
    $query = "SELECT * FROM tickets_generated WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, TicketGenerated::class);
    $ticketGenerated = $stmt->fetch();
    return $ticketGenerated ?: null;
  }

  // Récupérer un billet par code unique
  public function getTicketGeneratedByUniqueCode(string $uniqueCode): ?TicketGenerated {
    $query = "SELECT * FROM tickets_generated WHERE unique_code = :unique_code AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':unique_code', $uniqueCode);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, TicketGenerated::class);
    $ticketGenerated = $stmt->fetch();
    return $ticketGenerated ?: null;
  }

  // Récupérer tous les billets d'un article de commande
  public function getTicketsByOrderItemId(int $orderItemId): array {
    $query = "SELECT * FROM tickets_generated WHERE order_item_id = :order_item_id AND is_deleted = FALSE ORDER BY id ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_item_id', $orderItemId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, TicketGenerated::class);
    return $stmt->fetchAll();
  }

  // Créer un billet généré
  public function createTicketGenerated(TicketGenerated $ticketGenerated): ?int {
    $query = "INSERT INTO tickets_generated (order_item_id, qr_code, unique_code, is_used, created_at)
              VALUES (:order_item_id, :qr_code, :unique_code, FALSE, NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_item_id', $ticketGenerated->order_item_id, PDO::PARAM_INT);
    $stmt->bindParam(':qr_code', $ticketGenerated->qr_code);
    $stmt->bindParam(':unique_code', $ticketGenerated->unique_code);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Marquer un billet comme utilisé
  public function markAsUsed(int $id): bool {
    $query = "UPDATE tickets_generated SET is_used = TRUE, used_at = NOW() WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Marquer un billet comme utilisé par code unique
  public function markAsUsedByUniqueCode(string $uniqueCode): bool {
    $query = "UPDATE tickets_generated SET is_used = TRUE, used_at = NOW() WHERE unique_code = :unique_code AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':unique_code', $uniqueCode);
    return $stmt->execute();
  }

  // Supprimer un billet généré (soft delete)
  public function deleteTicketGenerated(int $id): bool {
    $query = "UPDATE tickets_generated SET is_deleted = TRUE WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Récupérer tous les billets générés pour une commande
  public function getTicketsByOrderId(int $orderId): array {
    $query = "SELECT tg.* FROM tickets_generated tg
              INNER JOIN order_items oi ON tg.order_item_id = oi.id
              WHERE oi.order_id = :order_id AND tg.is_deleted = FALSE
              ORDER BY tg.id ASC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, TicketGenerated::class);
    return $stmt->fetchAll();
  }

  // Récupérer tous les billets d'un utilisateur
  public function getTicketsByUserId(int $userId): array {
    $query = "SELECT tg.* FROM tickets_generated tg
              INNER JOIN order_items oi ON tg.order_item_id = oi.id
              INNER JOIN orders o ON oi.order_id = o.id
              WHERE o.user_id = :user_id AND tg.is_deleted = FALSE AND o.is_deleted = FALSE
              ORDER BY tg.created_at DESC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, TicketGenerated::class);
    return $stmt->fetchAll();
  }

  // Alias pour getTicketById
  public function getTicketById(int $id): ?TicketGenerated {
    return $this->getTicketGeneratedById($id);
  }

  // Alias pour getTicketByUniqueCode
  public function getTicketByUniqueCode(string $uniqueCode): ?TicketGenerated {
    return $this->getTicketGeneratedByUniqueCode($uniqueCode);
  }

  // Alias pour markTicketAsUsed
  public function markTicketAsUsed(int $id): bool {
    return $this->markAsUsed($id);
  }
}
