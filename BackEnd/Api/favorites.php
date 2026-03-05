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
use App\Repositories\FavoriteRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;

// Validators
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\FavoriteService;
use App\Services\SessionService;

// Utils
use App\Utils\Logger;

// Repositories
$favoriteRepository = new FavoriteRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$favoriteService = new FavoriteService($favoriteRepository, $eventRepository);

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
    case 'getMyFavorites':
      $result = $favoriteService->getUserFavorites($userId);
      echo json_encode($result);
      break;

    case 'add':
      if (!isset($data['event_id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID de l\'événement non fourni'
        ]);
        exit;
      }

      $result = $favoriteService->addFavorite($userId, (int) $data['event_id']);
      echo json_encode($result);
      break;

    case 'remove':
      if (!isset($data['event_id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID de l\'événement non fourni'
        ]);
        exit;
      }

      $result = $favoriteService->removeFavorite($userId, (int) $data['event_id']);
      echo json_encode($result);
      break;

    case 'isFavorite':
      if (!isset($data['event_id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID de l\'événement non fourni'
        ]);
        exit;
      }

      $result = $favoriteService->isFavorite($userId, (int) $data['event_id']);
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
  Logger::error('Favorites API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
