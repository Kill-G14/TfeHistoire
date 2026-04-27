<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\TicketGenerated;
use App\Repositories\PaymentRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\EventRepository;

class StripeService {
    private PaymentRepository $paymentRepository;
    private OrderRepository $orderRepository;
    private PurchasedTicketRepository $purchasedTicketRepository;
    private OrderItemRepository $orderItemRepository;
    private EventRepository $eventRepository;
    private PdfService $pdfService;
    private string $secretKey;
    private string $webhookSecret;
    private string $currency;

    public function __construct(
        PaymentRepository $paymentRepository,
        OrderRepository $orderRepository,
        ?PurchasedTicketRepository $purchasedTicketRepository = null,
        ?OrderItemRepository $orderItemRepository = null,
        ?EventRepository $eventRepository = null
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->orderRepository = $orderRepository;
        
        // Initialiser les dépendances si non fournies (rétrocompatibilité)
        $this->purchasedTicketRepository = $purchasedTicketRepository ?? new PurchasedTicketRepository();
        $this->orderItemRepository = $orderItemRepository ?? new OrderItemRepository();
        $this->eventRepository = $eventRepository ?? new EventRepository();
        $this->pdfService = new PdfService($this->eventRepository, $this->orderItemRepository);

        // Charger la configuration
        $config = require __DIR__ . '/../../config.php';
        $this->secretKey = $config['stripe']['secret_key'];
        $this->webhookSecret = $config['stripe']['webhook_secret'];
        $this->currency = $config['stripe']['currency'];

        // Initialiser Stripe
        require_once __DIR__ . '/../../vendor/stripe/stripe-php-master/init.php';
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    /**
     * Créer une session de checkout Stripe
     */
    public function createCheckoutSession(int $orderId, array $orderItems): array {
        try {
            // Récupérer la commande
            $order = $this->orderRepository->getOrderById($orderId);
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Commande introuvable'
                ];
            }

            // Si le montant est 0 (réservation gratuite), traiter différemment
            if ($order->total_price <= 0) {
                return $this->processFreeReservation($orderId);
            }

            $config = require __DIR__ . '/../../config.php';

            // Préparer les line items pour Stripe
            $lineItems = [];
            foreach ($orderItems as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $this->currency,
                        'product_data' => [
                            'name' => $item['name'],
                            'description' => $item['description'] ?? '',
                        ],
                        'unit_amount' => (int)($item['price'] * 100), // Convertir en centimes
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            // Créer la session de checkout
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $config['stripe']['success_url'] . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $config['stripe']['cancel_url'],
                'metadata' => [
                    'order_id' => $orderId,
                ],
                'customer_email' => $order->user_email ?? null,
            ]);

            // Enregistrer le paiement dans la base de données
            $payment = new Payment();
            $payment->order_id = $orderId;
            $payment->stripe_checkout_session_id = $session->id;
            $payment->stripe_payment_intent_id = null; // Sera mis à jour par le webhook
            $payment->amount = $order->total_price;
            $payment->currency = $this->currency;
            $payment->status = 'pending';
            $payment->metadata = json_encode([
                'order_id' => $orderId,
                'user_id' => $order->user_id,
            ]);

            $paymentId = $this->paymentRepository->createPayment($payment);

            if (!$paymentId) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement du paiement'
                ];
            }

            return [
                'success' => true,
                'message' => 'Session de checkout créée',
                'data' => [
                    'session_id' => $session->id,
                    'url' => $session->url,
                    'payment_id' => $paymentId
                ]
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur Stripe : ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter un webhook Stripe
     */
    public function handleWebhook(string $payload, string $signature): array {
        try {
            // Vérifier la signature du webhook
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $this->webhookSecret
            );

            // Traiter l'événement selon son type
            switch ($event->type) {
                case 'checkout.session.completed':
                    return $this->handleCheckoutSessionCompleted($event->data->object);

                case 'payment_intent.succeeded':
                    return $this->handlePaymentIntentSucceeded($event->data->object);

                case 'payment_intent.payment_failed':
                    return $this->handlePaymentIntentFailed($event->data->object);

                case 'charge.refunded':
                    return $this->handleChargeRefunded($event->data->object);

                default:
                    return [
                        'success' => true,
                        'message' => 'Événement non géré : ' . $event->type
                    ];
            }

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return [
                'success' => false,
                'message' => 'Signature invalide'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Gérer l'événement checkout.session.completed
     */
    private function handleCheckoutSessionCompleted($session): array {
        $orderId = $session->metadata->order_id ?? null;
        if (!$orderId) {
            return ['success' => false, 'message' => 'Order ID manquant'];
        }

        // Récupérer le paiement
        $payment = $this->paymentRepository->getPaymentByCheckoutSessionId($session->id);
        if (!$payment) {
            return ['success' => false, 'message' => 'Paiement introuvable'];
        }

        // Mettre à jour le Payment Intent ID
        if ($session->payment_intent) {
            $this->paymentRepository->updatePaymentIntentId($payment->id, $session->payment_intent);
        }

        return ['success' => true, 'message' => 'Session complétée'];
    }

    /**
     * Gérer l'événement payment_intent.succeeded
     */
    private function handlePaymentIntentSucceeded($paymentIntent): array {
        // Récupérer le paiement
        $payment = $this->paymentRepository->getPaymentByPaymentIntentId($paymentIntent->id);
        if (!$payment) {
            return ['success' => false, 'message' => 'Paiement introuvable'];
        }

        // Mettre à jour le statut du paiement
        $this->paymentRepository->updatePaymentStatus($payment->id, 'succeeded');

        // Mettre à jour les informations de paiement
        if (isset($paymentIntent->charges->data[0])) {
            $charge = $paymentIntent->charges->data[0];
            $paymentMethod = $charge->payment_method_details->type ?? 'card';
            $receiptUrl = $charge->receipt_url ?? '';
            $this->paymentRepository->updatePaymentFromStripe($payment->id, $paymentMethod, $receiptUrl);
        }

        // Mettre à jour le statut de la commande
        $this->orderRepository->updateOrderStatus(
            $payment->order_id,
            false, // is_pending
            true,  // is_paid
            false, // is_failed
            false  // is_cancelled
        );

        // Mettre à jour le payment_id dans la commande
        $this->orderRepository->updatePaymentId($payment->order_id, $paymentIntent->id);

        // Générer les tickets et PDFs
        $this->generateTicketsAndPdfs($payment->order_id);

        return ['success' => true, 'message' => 'Paiement réussi'];
    }

    /**
     * Gérer l'événement payment_intent.payment_failed
     */
    private function handlePaymentIntentFailed($paymentIntent): array {
        // Récupérer le paiement
        $payment = $this->paymentRepository->getPaymentByPaymentIntentId($paymentIntent->id);
        if (!$payment) {
            return ['success' => false, 'message' => 'Paiement introuvable'];
        }

        // Mettre à jour le statut du paiement
        $errorMessage = $paymentIntent->last_payment_error->message ?? 'Erreur de paiement';
        $this->paymentRepository->updatePaymentStatus($payment->id, 'failed', $errorMessage);

        // Mettre à jour le statut de la commande
        $this->orderRepository->updateOrderStatus(
            $payment->order_id,
            false, // is_pending
            false, // is_paid
            true,  // is_failed
            false  // is_cancelled
        );

        return ['success' => true, 'message' => 'Paiement échoué enregistré'];
    }

    /**
     * Gérer l'événement charge.refunded
     */
    private function handleChargeRefunded($charge): array {
        $paymentIntentId = $charge->payment_intent;
        
        // Récupérer le paiement
        $payment = $this->paymentRepository->getPaymentByPaymentIntentId($paymentIntentId);
        if (!$payment) {
            return ['success' => false, 'message' => 'Paiement introuvable'];
        }

        // Enregistrer le remboursement
        $refundAmount = $charge->amount_refunded / 100; // Convertir de centimes en euros
        $refundId = $charge->refunds->data[0]->id ?? 'unknown';
        
        $this->paymentRepository->recordRefund($payment->id, $refundId, $refundAmount);

        // Mettre à jour le statut de la commande
        $this->orderRepository->updateOrderStatus(
            $payment->order_id,
            false, // is_pending
            false, // is_paid
            false, // is_failed
            true   // is_cancelled (remboursé = annulé)
        );

        return ['success' => true, 'message' => 'Remboursement enregistré'];
    }

    /**
     * Créer un remboursement
     */
    public function createRefund(int $paymentId, ?float $amount = null): array {
        try {
            // Récupérer le paiement
            $payment = $this->paymentRepository->getPaymentById($paymentId);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Paiement introuvable'
                ];
            }

            if ($payment->status !== 'succeeded') {
                return [
                    'success' => false,
                    'message' => 'Le paiement doit être en statut succeeded pour être remboursé'
                ];
            }

            if (!$payment->stripe_payment_intent_id) {
                return [
                    'success' => false,
                    'message' => 'Payment Intent ID manquant'
                ];
            }

            // Créer le remboursement
            $refundParams = [
                'payment_intent' => $payment->stripe_payment_intent_id,
            ];

            // Si un montant est spécifié, remboursement partiel
            if ($amount !== null) {
                $refundParams['amount'] = (int)($amount * 100); // Convertir en centimes
            }

            $refund = \Stripe\Refund::create($refundParams);

            // Le webhook charge.refunded mettra à jour la base de données
            // Mais on peut déjà mettre à jour le statut ici
            $refundAmount = $refund->amount / 100;
            $this->paymentRepository->recordRefund($payment->id, $refund->id, $refundAmount);

            return [
                'success' => true,
                'message' => 'Remboursement créé',
                'data' => [
                    'refund_id' => $refund->id,
                    'amount' => $refundAmount,
                    'status' => $refund->status
                ]
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur Stripe : ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les détails d'un paiement depuis Stripe
     */
    public function getPaymentDetails(string $paymentIntentId): array {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'data' => [
                    'id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'created' => date('Y-m-d H:i:s', $paymentIntent->created),
                ]
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'message' => 'Erreur Stripe : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traiter une réservation gratuite (montant 0€)
     * Pas besoin de passer par Stripe, on valide directement la commande
     */
    private function processFreeReservation(int $orderId): array {
        try {
            // Créer un "paiement" fictif avec statut succeeded
            $payment = new Payment();
            $payment->order_id = $orderId;
            $payment->stripe_checkout_session_id = 'free_reservation_' . time();
            $payment->stripe_payment_intent_id = 'free_' . $orderId . '_' . time();
            $payment->amount = 0.00;
            $payment->currency = $this->currency;
            $payment->status = 'succeeded';
            $payment->payment_method = 'free_reservation';
            $payment->metadata = json_encode([
                'order_id' => $orderId,
                'type' => 'free_reservation',
                'processed_at' => date('Y-m-d H:i:s')
            ]);

            $paymentId = $this->paymentRepository->createPayment($payment);

            if (!$paymentId) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement de la réservation'
                ];
            }

            // Mettre à jour la commande comme payée
            $this->orderRepository->updateOrderStatus(
                $orderId,
                false, // is_pending
                true,  // is_paid
                false, // is_failed
                false  // is_cancelled
            );

            // Mettre à jour le payment_id dans la commande
            $this->orderRepository->updatePaymentId($orderId, $payment->stripe_payment_intent_id);

            // Générer les tickets et PDFs
            $this->generateTicketsAndPdfs($orderId);

            $config = require __DIR__ . '/../../config.php';

            return [
                'success' => true,
                'message' => 'Réservation gratuite confirmée',
                'data' => [
                    'is_free' => true,
                    'order_id' => $orderId,
                    'payment_id' => $paymentId,
                    'redirect_url' => $config['stripe']['success_url'] . '?order_id=' . $orderId . '&free=1'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Générer les tickets et PDFs pour une commande payée
     */
    private function generateTicketsAndPdfs(int $orderId): void {
        try {
            // Récupérer les items de la commande
            $order = $this->orderRepository->getOrderById($orderId);
            if (!$order) {
                return;
            }

            // Récupérer tous les order items
            $orderItems = $this->orderItemRepository->getOrderItemsByOrderId($orderId);
            
            foreach ($orderItems as $orderItem) {
                // Pour chaque quantité, créer un ticket
                for ($i = 0; $i < $orderItem->quantity; $i++) {
                    // Générer un code unique pour le ticket
                    $uniqueCode = $this->generateUniqueTicketCode();
                    
                    // Créer le ticket_generated
                    $ticketGenerated = new TicketGenerated();
                    $ticketGenerated->order_item_id = $orderItem->id;
                    $ticketGenerated->unique_code = $uniqueCode;
                    $ticketGenerated->qr_code = $this->generateQRCodePath($uniqueCode);
                    $ticketGenerated->is_used = false;
                    $ticketGenerated->is_deleted = false;
                    
                    $ticketId = $this->purchasedTicketRepository->createTicketGenerated($ticketGenerated);
                    
                    if ($ticketId) {
                        // Récupérer le ticket créé
                        $createdTicket = $this->purchasedTicketRepository->getTicketGeneratedById($ticketId);
                        
                        // Générer le PDF
                        if ($createdTicket) {
                            $this->pdfService->generateTicketPdf($createdTicket, $order->user_id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas faire échouer la transaction
            error_log('Erreur génération tickets/PDFs : ' . $e->getMessage());
        }
    }

    /**
     * Générer un code unique pour un ticket
     */
    private function generateUniqueTicketCode(): string {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    /**
     * Générer le chemin du QR code
     */
    private function generateQRCodePath(string $uniqueCode): string {
        // Pour l'instant, retourner un placeholder
        // TODO: Implémenter la génération réelle de QR code
        return 'qr_' . $uniqueCode . '.png';
    }
}
