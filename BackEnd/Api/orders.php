<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Gestion de la requête OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Repositories
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\TicketRepository;
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;

// Validators
use App\Validators\OrderValidator;
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\SessionService;

// Utils
use App\Utils\Logger;

// Repositories
$orderRepository = new OrderRepository();
$orderItemRepository = new OrderItemRepository();
$ticketRepository = new TicketRepository();
$purchasedTicketRepository = new PurchasedTicketRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$orderValidator = new OrderValidator();
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$orderService = new OrderService($orderRepository, $orderItemRepository, $ticketRepository, $purchasedTicketRepository, $orderValidator);

try {
  // Récupération des données JSON
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);

  if (!$data || !isset($data['action'])) {
    echo json_encode([
      'success' => false,
      'message' => 'Action non spécifiée'
    ]);
    exit;
  }

  // Vérifier l'authentification
  if (!isset($data['token']) || empty($data['token'])) {
    echo json_encode([
      'success' => false,
      'message' => 'Non authentifié'
    ]);
    exit;
  }

  $token = $data['token'];
  $userId = $authService->checkToken($token);

  if (!$userId) {
    echo json_encode([
      'success' => false,
      'message' => 'Token invalide'
    ]);
    exit;
  }

  $action = $data['action'];

  // Routing par action
  switch ($action) {
    case 'getMyOrders':
      $result = $orderService->getOrdersByUserId($userId);
      echo json_encode($result);
      break;

    case 'getOrderById':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $orderService->getOrderById((int) $data['id'], $userId);
      echo json_encode($result);
      break;

    case 'create':
      $data['user_id'] = $userId;
      $result = $orderService->createOrder($data, $userId);
      echo json_encode($result);
      break;

    case 'cancel':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $orderService->cancelOrder((int) $data['id'], $userId);
      echo json_encode($result);
      break;

    default:
      echo json_encode([
        'success' => false,
        'message' => 'Action inconnue'
      ]);
      break;
  }

} catch (Exception $e) {
  Logger::error('Orders API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
