<?php

namespace App\Repositories;

use App\Models\Order;
use App\Utils\Database;
use PDO;

class OrderRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer une commande par ID
  public function getOrderById(int $id): ?Order {
    $query = "SELECT * FROM orders WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Order::class);
    $order = $stmt->fetch();
    return $order ?: null;
  }

  // Récupérer les commandes par utilisateur
  public function getOrdersByUserId(int $userId): array {
    $query = "SELECT * FROM orders WHERE user_id = :user_id AND is_deleted = FALSE ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Order::class);
    return $stmt->fetchAll();
  }

  // Créer une commande
  public function createOrder(Order $order): ?int {
    $query = "INSERT INTO orders (user_id, total_price, is_pending, is_paid, is_failed, is_cancelled, payment_provider, payment_id, created_at, updated_at)
              VALUES (:user_id, :total_price, :is_pending, :is_paid, :is_failed, :is_cancelled, :payment_provider, :payment_id, NOW(), NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $order->user_id, PDO::PARAM_INT);
    $stmt->bindParam(':total_price', $order->total_price);
    $stmt->bindParam(':is_pending', $order->is_pending, PDO::PARAM_INT);
    $stmt->bindParam(':is_paid', $order->is_paid, PDO::PARAM_INT);
    $stmt->bindParam(':is_failed', $order->is_failed, PDO::PARAM_INT);
    $stmt->bindParam(':is_cancelled', $order->is_cancelled, PDO::PARAM_INT);
    $stmt->bindParam(':payment_provider', $order->payment_provider);
    $stmt->bindParam(':payment_id', $order->payment_id);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Mettre à jour le statut de paiement
  public function updateOrderStatus(int $id, bool $isPending, bool $isPaid, bool $isFailed, bool $isCancelled): bool {
    $query = "UPDATE orders SET 
              is_pending = :is_pending,
              is_paid = :is_paid,
              is_failed = :is_failed,
              is_cancelled = :is_cancelled,
              updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':is_pending', $isPending, PDO::PARAM_INT);
    $stmt->bindParam(':is_paid', $isPaid, PDO::PARAM_INT);
    $stmt->bindParam(':is_failed', $isFailed, PDO::PARAM_INT);
    $stmt->bindParam(':is_cancelled', $isCancelled, PDO::PARAM_INT);
    
    return $stmt->execute();
  }

  // Marquer une commande comme payée
  public function markAsPaid(int $id): bool {
    return $this->updateOrderStatus($id, false, true, false, false);
  }

  // Marquer une commande comme échouée
  public function markAsFailed(int $id): bool {
    return $this->updateOrderStatus($id, false, false, true, false);
  }

  // Annuler une commande
  public function cancelOrder(int $id): bool {
    return $this->updateOrderStatus($id, false, false, false, true);
  }

  // Supprimer une commande (soft delete)
  public function deleteOrder(int $id): bool {
    $query = "UPDATE orders SET is_deleted = TRUE, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Mettre à jour le payment_id
  public function updatePaymentId(int $id, string $paymentId): bool {
    $query = "UPDATE orders SET payment_id = :payment_id, updated_at = NOW() WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':payment_id', $paymentId);
    return $stmt->execute();
  }
}
