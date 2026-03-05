<?php

namespace App\Repositories;

use App\Models\OrderItem;
use App\Utils\Database;
use PDO;

class OrderItemRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un article de commande par ID
  public function getOrderItemById(int $id): ?OrderItem {
    $query = "SELECT * FROM order_items WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, OrderItem::class);
    $orderItem = $stmt->fetch();
    return $orderItem ?: null;
  }

  // Récupérer tous les articles d'une commande
  public function getOrderItemsByOrderId(int $orderId): array {
    $query = "SELECT * FROM order_items WHERE order_id = :order_id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, OrderItem::class);
    return $stmt->fetchAll();
  }

  // Créer un article de commande
  public function createOrderItem(OrderItem $orderItem): ?int {
    $query = "INSERT INTO order_items (order_id, ticket_id, quantity, unit_price, subtotal, created_at)
              VALUES (:order_id, :ticket_id, :quantity, :unit_price, :subtotal, NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_id', $orderItem->order_id, PDO::PARAM_INT);
    $stmt->bindParam(':ticket_id', $orderItem->ticket_id, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $orderItem->quantity, PDO::PARAM_INT);
    $stmt->bindParam(':unit_price', $orderItem->unit_price);
    $stmt->bindParam(':subtotal', $orderItem->subtotal);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Supprimer un article de commande (soft delete)
  public function deleteOrderItem(int $id): bool {
    $query = "UPDATE order_items SET is_deleted = TRUE WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Récupérer les tickets d'une commande avec leurs détails
  public function getOrderItemsWithTicketDetails(int $orderId): array {
    $query = "SELECT oi.*, t.name as ticket_name, t.event_id, e.title as event_title 
              FROM order_items oi
              INNER JOIN tickets t ON oi.ticket_id = t.id
              INNER JOIN events e ON t.event_id = e.id
              WHERE oi.order_id = :order_id AND oi.is_deleted = FALSE
              ORDER BY oi.id ASC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
