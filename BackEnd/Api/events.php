<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Gestion de la requête OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Repositories
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;

// Validators
use App\Validators\EventValidator;
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\EventService;

// Utils
use App\Utils\Logger;

// Repositories
$eventRepository = new EventRepository();
$userRepository = new UserRepository();

// Validators
$eventValidator = new EventValidator();
$userValidator = new UserValidator();

// Services
$authService = new AuthService($userRepository, $userValidator);
$eventService = new EventService($eventRepository, $eventValidator);

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

  $action = $data['action'];

  // Routing par action
  switch ($action) {
    case 'getAll':
      $result = $eventService->getAllEvents();
      echo json_encode($result);
      break;

    case 'getById':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $eventService->getEventById((int) $data['id']);
      echo json_encode($result);
      break;

    case 'getByCountry':
      if (!isset($data['country'])) {
        echo json_encode([
          'success' => false,
          'message' => 'Pays non fourni'
        ]);
        exit;
      }

      $result = $eventService->getEventsByCountry($data['country']);
      echo json_encode($result);
      break;

    case 'getByCategory':
      if (!isset($data['category'])) {
        echo json_encode([
          'success' => false,
          'message' => 'Catégorie non fournie'
        ]);
        exit;
      }

      $result = $eventService->getEventsByCategory($data['category']);
      echo json_encode($result);
      break;

    case 'search':
      if (!isset($data['search'])) {
        echo json_encode([
          'success' => false,
          'message' => 'Terme de recherche non fourni'
        ]);
        exit;
      }

      $result = $eventService->searchEvents($data['search']);
      echo json_encode($result);
      break;

    case 'create':
      // Vérifier l'authentification
      $headers = getallheaders();
      $token = $headers['Authorization'] ?? null;

      if (!$token) {
        echo json_encode([
          'success' => false,
          'message' => 'Non authentifié'
        ]);
        exit;
      }

      $token = str_replace('Bearer ', '', $token);
      $userId = $authService->verifyToken($token);

      if (!$userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Token invalide'
        ]);
        exit;
      }

      $result = $eventService->createEvent($data, $userId);
      echo json_encode($result);
      break;

    case 'update':
      // Vérifier l'authentification
      $headers = getallheaders();
      $token = $headers['Authorization'] ?? null;

      if (!$token) {
        echo json_encode([
          'success' => false,
          'message' => 'Non authentifié'
        ]);
        exit;
      }

      $token = str_replace('Bearer ', '', $token);
      $userId = $authService->verifyToken($token);

      if (!$userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Token invalide'
        ]);
        exit;
      }

      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $eventService->updateEvent((int) $data['id'], $data, $userId);
      echo json_encode($result);
      break;

    case 'delete':
      // Vérifier l'authentification
      $headers = getallheaders();
      $token = $headers['Authorization'] ?? null;

      if (!$token) {
        echo json_encode([
          'success' => false,
          'message' => 'Non authentifié'
        ]);
        exit;
      }

      $token = str_replace('Bearer ', '', $token);
      $userId = $authService->verifyToken($token);

      if (!$userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Token invalide'
        ]);
        exit;
      }

      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $eventService->deleteEvent((int) $data['id'], $userId);
      echo json_encode($result);
      break;

    case 'getMyEvents':
      // Vérifier l'authentification
      $headers = getallheaders();
      $token = $headers['Authorization'] ?? null;

      if (!$token) {
        echo json_encode([
          'success' => false,
          'message' => 'Non authentifié'
        ]);
        exit;
      }

      $token = str_replace('Bearer ', '', $token);
      $userId = $authService->verifyToken($token);

      if (!$userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Token invalide'
        ]);
        exit;
      }

      $result = $eventService->getEventsByUserId($userId);
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
  Logger::error('Events API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
