<?php

namespace App\Repositories;

use App\Models\Payment;
use App\Utils\Database;
use PDO;

class PaymentRepository {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    private function getPdo(): PDO {
        return $this->pdo;
    }

    // Créer un paiement
    public function createPayment(Payment $payment): ?int {
        $query = "INSERT INTO payments (
                    order_id, 
                    stripe_payment_intent_id, 
                    stripe_checkout_session_id, 
                    amount, 
                    currency, 
                    status, 
                    payment_method, 
                    receipt_url, 
                    metadata, 
                    created_at, 
                    updated_at
                  ) VALUES (
                    :order_id, 
                    :stripe_payment_intent_id, 
                    :stripe_checkout_session_id, 
                    :amount, 
                    :currency, 
                    :status, 
                    :payment_method, 
                    :receipt_url, 
                    :metadata, 
                    NOW(), 
                    NOW()
                  )";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':order_id', $payment->order_id, PDO::PARAM_INT);
        $stmt->bindParam(':stripe_payment_intent_id', $payment->stripe_payment_intent_id, PDO::PARAM_STR);
        $stmt->bindParam(':stripe_checkout_session_id', $payment->stripe_checkout_session_id, PDO::PARAM_STR);
        $stmt->bindParam(':amount', $payment->amount);
        $stmt->bindParam(':currency', $payment->currency, PDO::PARAM_STR);
        $stmt->bindParam(':status', $payment->status, PDO::PARAM_STR);
        $stmt->bindParam(':payment_method', $payment->payment_method, PDO::PARAM_STR);
        $stmt->bindParam(':receipt_url', $payment->receipt_url, PDO::PARAM_STR);
        $stmt->bindParam(':metadata', $payment->metadata, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return (int) $this->getPdo()->lastInsertId();
        }
        
        return null;
    }

    // Récupérer un paiement par ID
    public function getPaymentById(int $id): ?Payment {
        $query = "SELECT * FROM payments WHERE id = :id";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Payment::class);
        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    // Récupérer un paiement par Payment Intent ID
    public function getPaymentByPaymentIntentId(string $paymentIntentId): ?Payment {
        $query = "SELECT * FROM payments WHERE stripe_payment_intent_id = :payment_intent_id";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':payment_intent_id', $paymentIntentId, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Payment::class);
        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    // Récupérer un paiement par Checkout Session ID
    public function getPaymentByCheckoutSessionId(string $sessionId): ?Payment {
        $query = "SELECT * FROM payments WHERE stripe_checkout_session_id = :session_id";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Payment::class);
        $payment = $stmt->fetch();
        return $payment ?: null;
    }

    // Récupérer les paiements par commande
    public function getPaymentsByOrderId(int $orderId): array {
        $query = "SELECT * FROM payments WHERE order_id = :order_id ORDER BY created_at DESC";
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, Payment::class);
        return $stmt->fetchAll();
    }

    // Mettre à jour le statut du paiement
    public function updatePaymentStatus(int $id, string $status, ?string $errorMessage = null): bool {
        $query = "UPDATE payments SET 
                  status = :status, 
                  error_message = :error_message, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':error_message', $errorMessage, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    // Mettre à jour le Payment Intent ID
    public function updatePaymentIntentId(int $id, string $paymentIntentId): bool {
        $query = "UPDATE payments SET 
                  stripe_payment_intent_id = :payment_intent_id, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':payment_intent_id', $paymentIntentId, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    // Enregistrer un remboursement
    public function recordRefund(int $id, string $refundId, float $refundAmount): bool {
        $query = "UPDATE payments SET 
                  status = 'refunded',
                  refund_id = :refund_id, 
                  refund_amount = :refund_amount, 
                  refunded_at = NOW(), 
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':refund_id', $refundId, PDO::PARAM_STR);
        $stmt->bindParam(':refund_amount', $refundAmount);
        
        return $stmt->execute();
    }

    // Mettre à jour les informations de paiement depuis Stripe
    public function updatePaymentFromStripe(int $id, string $paymentMethod, string $receiptUrl): bool {
        $query = "UPDATE payments SET 
                  payment_method = :payment_method, 
                  receipt_url = :receipt_url, 
                  updated_at = NOW()
                  WHERE id = :id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':payment_method', $paymentMethod, PDO::PARAM_STR);
        $stmt->bindParam(':receipt_url', $receiptUrl, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
}
