<?php

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
use App\Repositories\ReservationRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Services\ReservationService;
use App\Services\SessionService;
use App\Services\EmailService;

// Instances
$reservationRepository = new ReservationRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
$sessionService = new SessionService($sessionRepository);
$emailService = new EmailService($userRepository);
$reservationService = new ReservationService(
  $reservationRepository,
  $eventRepository,
  $userRepository,
  $emailService
);

// Récupération des données de la requête
$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'create':
    // Créer une réservation
    $token = $request['token'] ?? '';
    $eventId = $request['event_id'] ?? 0;
    $quantity = $request['quantity'] ?? 1;

    if (!$token) {
      $response = ['success' => false, 'message' => 'Token manquant'];
    } else {
      // Valider le token
      $userId = $sessionService->getUserIdByToken($token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide ou expiré'];
      } else {
        $response = $reservationService->createReservation($userId, $eventId, $quantity);
      }
    }
    break;

  case 'getMyReservations':
    // Récupérer les réservations de l'utilisateur
    $token = $request['token'] ?? '';

    if (!$token) {
      $response = ['success' => false, 'message' => 'Token manquant'];
    } else {
      // Valider le token
      $userId = $sessionService->getUserIdByToken($token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide ou expiré'];
      } else {
        $response = $reservationService->getUserReservations($userId);
      }
    }
    break;

  case 'cancel':
    // Annuler une réservation
    $token = $request['token'] ?? '';
    $reservationId = $request['reservation_id'] ?? 0;

    if (!$token) {
      $response = ['success' => false, 'message' => 'Token manquant'];
    } else {
      // Valider le token
      $userId = $sessionService->getUserIdByToken($token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide ou expiré'];
      } else {
        $response = $reservationService->cancelReservation($reservationId, $userId);
      }
    }
    break;

  case 'getAvailableTickets':
    // Récupérer le nombre de places disponibles
    $eventId = $request['event_id'] ?? 0;
    $response = $reservationService->getAvailableTickets($eventId);
    break;

  case 'checkReservation':
    // Vérifier si l'utilisateur a déjà réservé
    $token = $request['token'] ?? '';
    $eventId = $request['event_id'] ?? 0;

    if (!$token) {
      $response = ['success' => false, 'message' => 'Token manquant'];
    } else {
      // Valider le token
      $userId = $sessionService->getUserIdByToken($token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide ou expiré'];
      } else {
        $hasReservation = $reservationRepository->hasReservation($userId, $eventId);
        $response = [
          'success' => true,
          'data' => ['has_reservation' => $hasReservation]
        ];
      }
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
