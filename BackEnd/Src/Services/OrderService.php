<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketGenerated;
use App\Models\ModelsDTO\OrderDTO;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\EventRepository;
use App\Repositories\PurchasedTicketRepository;
use App\Validators\OrderValidator;
use App\Utils\Logger;

class OrderService {
  private OrderRepository $orderRepository;
  private OrderItemRepository $orderItemRepository;
  private EventRepository $eventRepository;
  private PurchasedTicketRepository $purchasedTicketRepository;
  private OrderValidator $orderValidator;

  public function __construct(
    OrderRepository $orderRepository,
    OrderItemRepository $orderItemRepository,
    EventRepository $eventRepository,
    PurchasedTicketRepository $purchasedTicketRepository,
    OrderValidator $orderValidator
  ) {
    $this->orderRepository = $orderRepository;
    $this->orderItemRepository = $orderItemRepository;
    $this->eventRepository = $eventRepository;
    $this->purchasedTicketRepository = $purchasedTicketRepository;
    $this->orderValidator = $orderValidator;
  }

  // Récupérer les commandes d'un utilisateur
  public function getOrdersByUserId(int $userId): array {
    $orders = $this->orderRepository->getOrdersByUserId($userId);
    
    $orderDTOs = array_map(function($order) {
      return (new OrderDTO($order))->toArray();
    }, $orders);

    return [
      'success' => true,
      'data' => $orderDTOs
    ];
  }

  // Récupérer une commande par ID
  public function getOrderById(int $id, int $userId): array {
    $order = $this->orderRepository->getOrderById($id);

    if (!$order) {
      return [
        'success' => false,
        'message' => 'Commande non trouvée'
      ];
    }

    // Vérifier que l'utilisateur est le propriétaire
    if ($order->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à voir cette commande'
      ];
    }

    // Charger les items de la commande avec les détails des événements
    $items = $this->orderItemRepository->getOrderItemsWithTicketDetails($id);

    return [
      'success' => true,
      'data' => (new OrderDTO($order, $items))->toArray()
    ];
  }

  // Achat du tickets (création de la commande)
  public function createOrder(array $data, int $userId): array {
    // Validation
    $errors = $this->orderValidator->validateCreateOrder($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Calculer le total
    $totalPrice = 0;
    $itemsToCreate = [];

    foreach ($data['items'] as $item) {
      // Vérifier que l'événement existe
      $event = $this->eventRepository->getEventById($item['event_id']);
      if (!$event) {
        return [
          'success' => false,
          'message' => 'Événement avec ID ' . $item['event_id'] . ' non trouvé'
        ];
      }

      // Vérifier la disponibilité
      if ($event->ticket_quantity < $item['quantity']) {
        return [
          'success' => false,
          'message' => 'Quantité insuffisante pour l\'événement ' . $event->title
        ];
      }

      $subtotal = $event->ticket_price * $item['quantity'];
      $totalPrice += $subtotal;

      $itemsToCreate[] = [
        'event' => $event,
        'quantity' => $item['quantity'],
        'unit_price' => $event->ticket_price,
        'subtotal' => $subtotal
      ];
    }

    // Créer la commande
    $order = new Order();
    $order->user_id = $userId;
    $order->total_price = $totalPrice;
    $order->is_pending = true;
    $order->is_paid = false;
    $order->is_failed = false;
    $order->is_cancelled = false;
    $order->payment_provider = 'stripe';
    $order->payment_id = null;

    $orderId = $this->orderRepository->createOrder($order);

    if (!$orderId) {
      Logger::error('Failed to create order', ['user_id' => $userId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création de la commande'
      ];
    }

    // Créer les articles de commande
    foreach ($itemsToCreate as $itemData) {
      $orderItem = new OrderItem();
      $orderItem->order_id = $orderId;
      $orderItem->event_id = $itemData['event']->id;
      $orderItem->ticket_name = 'Billet Standard'; // Nom par défaut
      $orderItem->quantity = $itemData['quantity'];
      $orderItem->unit_price = $itemData['unit_price'];
      $orderItem->subtotal = $itemData['subtotal'];

      $orderItemId = $this->orderItemRepository->createOrderItem($orderItem);

      if (!$orderItemId) {
        Logger::error('Failed to create order item', ['order_id' => $orderId]);
        continue;
      }

      // Décrémenter la quantité de billets disponibles
      $this->eventRepository->decrementTicketQuantity($itemData['event']->id, $itemData['quantity']);
    }

    // Récupérer la commande créée
    $createdOrder = $this->orderRepository->getOrderById($orderId);

    Logger::info('Order created successfully', ['order_id' => $orderId]);

    return [
      'success' => true,
      'message' => 'Commande créée avec succès',
      'data' => (new OrderDTO($createdOrder))->toArray()
    ];
  }

  // Annuler une commande
  public function cancelOrder(int $id, int $userId): array {
    $order = $this->orderRepository->getOrderById($id);

    if (!$order) {
      return [
        'success' => false,
        'message' => 'Commande non trouvée'
      ];
    }

    // Vérifier que l'utilisateur est le propriétaire
    if ($order->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à annuler cette commande'
      ];
    }

    // Vérifier que la commande n'est pas déjà payée
    if ($order->is_paid) {
      return [
        'success' => false,
        'message' => 'Impossible d\'annuler une commande déjà payée'
      ];
    }

    // Récupérer les articles de commande
    $orderItems = $this->orderItemRepository->getOrderItemsByOrderId($id);

    // Remettre les quantités de billets
    foreach ($orderItems as $orderItem) {
      $this->eventRepository->incrementTicketQuantity($orderItem->event_id, $orderItem->quantity);
    }

    // Annuler la commande
    $success = $this->orderRepository->cancelOrder($id);

    if (!$success) {
      Logger::error('Failed to cancel order', ['order_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de l\'annulation de la commande'
      ];
    }

    Logger::info('Order cancelled successfully', ['order_id' => $id]);

    return [
      'success' => true,
      'message' => 'Commande annulée avec succès'
    ];
  }

  // Confirmer le paiement et générer les billets
  public function confirmPayment(int $orderId, string $paymentId): array {
    $order = $this->orderRepository->getOrderById($orderId);

    if (!$order) {
      return [
        'success' => false,
        'message' => 'Commande non trouvée'
      ];
    }

    // Vérifier que la commande n'est pas déjà payée
    if ($order->is_paid) {
      return [
        'success' => false,
        'message' => 'Cette commande est déjà payée'
      ];
    }

    // Marquer la commande comme payée
    $success = $this->orderRepository->markAsPaid($orderId, $paymentId);

    if (!$success) {
      Logger::error('Failed to mark order as paid', ['order_id' => $orderId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la confirmation du paiement'
      ];
    }

    // Récupérer les articles de commande
    $orderItems = $this->orderItemRepository->getOrderItemsByOrderId($orderId);

    // Générer les billets individuels
    foreach ($orderItems as $orderItem) {
      for ($i = 0; $i < $orderItem->quantity; $i++) {
        $ticketGenerated = new TicketGenerated();
        $ticketGenerated->order_item_id = $orderItem->id;
        $ticketGenerated->unique_code = $this->generateUniqueCode();
        $ticketGenerated->qr_code = $this->generateQRCode($ticketGenerated->unique_code);

        $ticketId = $this->purchasedTicketRepository->createTicketGenerated($ticketGenerated);

        if (!$ticketId) {
          Logger::error('Failed to generate ticket', [
            'order_item_id' => $orderItem->id,
            'iteration' => $i
          ]);
        }
      }
    }

    Logger::info('Payment confirmed and tickets generated', ['order_id' => $orderId]);

    return [
      'success' => true,
      'message' => 'Paiement confirmé et billets générés'
    ];
  }

  // Récupérer les billets générés pour une commande
  public function getGeneratedTicketsByOrderId(int $orderId, int $userId): array {
    $order = $this->orderRepository->getOrderById($orderId);

    if (!$order) {
      return [
        'success' => false,
        'message' => 'Commande non trouvée'
      ];
    }

    // Vérifier que l'utilisateur est le propriétaire
    if ($order->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à voir ces billets'
      ];
    }

    // Vérifier que la commande est payée
    if (!$order->is_paid) {
      return [
        'success' => false,
        'message' => 'Cette commande n\'est pas encore payée'
      ];
    }

    $tickets = $this->purchasedTicketRepository->getTicketsByOrderId($orderId);

    return [
      'success' => true,
      'data' => array_map(function($ticket) {
        return [
          'id' => $ticket->id,
          'order_item_id' => $ticket->order_item_id,
          'unique_code' => $ticket->unique_code,
          'qr_code' => $ticket->qr_code,
          'is_used' => $ticket->is_used,
          'used_at' => $ticket->used_at,
          'created_at' => $ticket->created_at
        ];
      }, $tickets)
    ];
  }

  // Générer un code unique
  private function generateUniqueCode(): string {
    return strtoupper(bin2hex(random_bytes(8)));
  }

  // Générer un QR code (URL vers le code unique)
  private function generateQRCode(string $uniqueCode): string {
    // Format simple : URL qui pointe vers la validation du billet
    return 'https://yourapp.com/validate/' . $uniqueCode;
  }
}
