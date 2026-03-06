<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\EventValidator;
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\EventService;
use App\Services\SessionService;

// Models
// repositories 
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$eventValidator = new EventValidator();
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$eventService = new EventService($eventRepository, $eventValidator);

$request = json_decode(file_get_contents("php://input"));

// Vérifier que la requête est valide
if (!$request || !isset($request->action)) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request->action) {

  case 'getAll':
    $response = $eventService->getAllEvents();
    break;

  case 'getById':
    if (!isset($request->id)) {
      $response = ['success' => false, 'message' => 'ID non fourni'];
    } else {
      $response = $eventService->getEventById((int) $request->id);
    }
    break;

  case 'getByCountry':
    if (!isset($request->country)) {
      $response = ['success' => false, 'message' => 'Pays non fourni'];
    } else {
      $response = $eventService->getEventsByCountry($request->country);
    }
    break;

  case 'getByCategory':
    if (!isset($request->category)) {
      $response = ['success' => false, 'message' => 'Catégorie non fournie'];
    } else {
      $response = $eventService->getEventsByCategory($request->category);
    }
    break;

  case 'search':
    if (!isset($request->search)) {
      $response = ['success' => false, 'message' => 'Terme de recherche non fourni'];
    } else {
      $response = $eventService->searchEvents($request->search);
    }
    break;

  case 'create':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request->token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $eventService->createEvent((array) $request, $userId);
      }
    }
    break;

  case 'update':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request->token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request->id)) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $eventService->updateEvent((int) $request->id, (array) $request, $userId);
        }
      }
    }
    break;

  case 'delete':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request->token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request->id)) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $eventService->deleteEvent((int) $request->id, $userId);
        }
      }
    }
    break;

  case 'getMyEvents':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request->token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $eventService->getEventsByUserId($userId);
      }
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
    }
    break;

  case 'getMyEvents':
    $userId = $authService->checkToken($request->token);
    if (!$userId) {
      $response = ['success' => false, 'message' => 'Token invalide'];
    } else {
      $response = $eventService->getEventsByUserId($userId);
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
