<?php

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

// Imports
use App\Repositories\EventRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\Services\PaymentService;
use App\Services\SessionService;

// Instances
$userRepository = new UserRepository();
$eventRepository = new EventRepository();
$reservationRepository = new ReservationRepository();
$sessionService = new SessionService(new SessionRepository());
$emailService = new EmailService($userRepository);
$paymentService = new PaymentService(
  $userRepository,
  $eventRepository,
  $reservationRepository,
  $emailService
);

// Récupération des données de la requête
$request = json_decode(file_get_contents("php://input"), true);

if (!$request || !isset($request['action'])) {
  echo json_encode(['success' => false, 'message' => 'Requête invalide']);
  exit;
}

// Toutes les actions de paiement exigent un utilisateur connecté
$token = $request['token'] ?? '';
$userId = $token ? $sessionService->getUserIdByToken($token) : null;

if (!$userId) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré']);
  exit;
}

switch ($request['action']) {
  case 'connectStripeAccount':
    // L'organisateur relie son compte Stripe (retourne l'URL d'inscription Stripe)
    $response = $paymentService->startOnboarding($userId);
    break;

  case 'getStripeAccountStatus':
    $response = $paymentService->getAccountStatus($userId);
    break;

  case 'createCheckoutSession':
    $response = $paymentService->createCheckoutSession($userId, (int) ($request['reservation_id'] ?? 0));
    break;

  case 'confirmReturn':
    $response = $paymentService->confirmReturn($userId, (int) ($request['reservation_id'] ?? 0));
    break;

  case 'abandonPayment':
    $response = $paymentService->abandonPayment($userId, (int) ($request['reservation_id'] ?? 0));
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
