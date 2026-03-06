<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\TicketRepository;
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\OrderValidator;
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\SessionService;

// Models
// repositories 
$orderRepository = new OrderRepository();
$orderItemRepository = new OrderItemRepository();
$ticketRepository = new TicketRepository();
$purchasedTicketRepository = new PurchasedTicketRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$orderValidator = new OrderValidator();
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$orderService = new OrderService($orderRepository, $orderItemRepository, $ticketRepository, $purchasedTicketRepository, $orderValidator);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

// Vérifier le token
if (!isset($request['token'])) {
  $response = ['success' => false, 'message' => 'Token non fourni'];
  echo json_encode($response);
  exit;
}

$userId = $authService->checkToken($request['token']);
if (!$userId) {
  $response = ['success' => false, 'message' => 'Token invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'getMyOrders':
    $response = $orderService->getOrdersByUserId($userId);
    break;

  case 'getOrderById':
    if (!isset($request['id'])) {
      $response = ['success' => false, 'message' => 'ID non fourni'];
    } else {
      $response = $orderService->getOrderById((int) $request['id'], $userId);
    }
    break;

  case 'create':
    $request['user_id'] = $userId;
    $response = $orderService->createOrder($request, $userId);
    break;

  case 'cancel':
    if (!isset($request['id'])) {
      $response = ['success' => false, 'message' => 'ID non fourni'];
    } else {
      $response = $orderService->cancelOrder((int) $request['id'], $userId);
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
