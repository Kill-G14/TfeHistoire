<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketGenerated;
use App\Models\ModelsDTO\OrderDTO;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\TicketRepository;
use App\Repositories\TicketGeneratedRepository;
use App\Validators\OrderValidator;
use App\Utils\Logger;

class OrderService {
  private OrderRepository $orderRepository;
  private OrderItemRepository $orderItemRepository;
  private TicketRepository $ticketRepository;
  private TicketGeneratedRepository $ticketGeneratedRepository;
  private OrderValidator $orderValidator;

  public function __construct(
    OrderRepository $orderRepository,
    OrderItemRepository $orderItemRepository,
    TicketRepository $ticketRepository,
    TicketGeneratedRepository $ticketGeneratedRepository,
    OrderValidator $orderValidator
  ) {
    $this->orderRepository = $orderRepository;
    $this->orderItemRepository = $orderItemRepository;
    $this->ticketRepository = $ticketRepository;
    $this->ticketGeneratedRepository = $ticketGeneratedRepository;
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

    return [
      'success' => true,
      'data' => (new OrderDTO($order))->toArray()
    ];
  }

  // Créer une commande
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
      // Vérifier que le billet existe
      $ticket = $this->ticketRepository->getTicketById($item['ticket_id']);
      if (!$ticket) {
        return [
          'success' => false,
          'message' => 'Billet avec ID ' . $item['ticket_id'] . ' non trouvé'
        ];
      }

      // Vérifier la disponibilité
      if ($ticket->quantity < $item['quantity']) {
        return [
          'success' => false,
          'message' => 'Quantité insuffisante pour le billet ' . $ticket->name
        ];
      }

      $subtotal = $ticket->price * $item['quantity'];
      $totalPrice += $subtotal;

      $itemsToCreate[] = [
        'ticket' => $ticket,
        'quantity' => $item['quantity'],
        'unit_price' => $ticket->price,
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
    $order->payment_provider = 'mollie';
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
      $orderItem->ticket_id = $itemData['ticket']->id;
      $orderItem->quantity = $itemData['quantity'];
      $orderItem->unit_price = $itemData['unit_price'];
      $orderItem->subtotal = $itemData['subtotal'];

      $orderItemId = $this->orderItemRepository->createOrderItem($orderItem);

      if (!$orderItemId) {
        Logger::error('Failed to create order item', ['order_id' => $orderId]);
        continue;
      }

      // Décrémenter la quantité de billets disponibles
      $this->ticketRepository->decrementQuantity($itemData['ticket']->id, $itemData['quantity']);
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
      $this->ticketRepository->incrementQuantity($orderItem->ticket_id, $orderItem->quantity);
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
}
