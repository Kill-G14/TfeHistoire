<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

// Models
// repositories 
use App\Repositories\EventRepository;
use App\Repositories\EventModificationRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\EventValidator;
use App\Validators\EventModificationValidator;
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\EventService;
use App\Services\EventModificationService;
use App\Services\EmailService;
use App\Services\SessionService;

// Models
// repositories 
$eventRepository = new EventRepository();
$eventModificationRepository = new EventModificationRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$eventValidator = new EventValidator();
$eventModificationValidator = new EventModificationValidator();
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$eventService = new EventService($eventRepository, $eventValidator);
$emailService = new EmailService($userRepository);
$eventModificationService = new EventModificationService(
  $eventRepository, 
  $eventModificationRepository, 
  $userRepository, 
  $emailService, 
  $eventModificationValidator
);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {

  case 'getAll':
    $response = $eventService->getAllEvents();
    break;

  case 'getById':
    if (!isset($request['id'])) {
      $response = ['success' => false, 'message' => 'ID non fourni'];
    } else {
      $response = $eventService->getEventById((int) $request['id']);
    }
    break;

  case 'getByCountry':
    if (!isset($request['country'])) {
      $response = ['success' => false, 'message' => 'Pays non fourni'];
    } else {
      $response = $eventService->getEventsByCountry($request['country']);
    }
    break;

  case 'getByCategory':
    if (!isset($request['category'])) {
      $response = ['success' => false, 'message' => 'Catégorie non fournie'];
    } else {
      $response = $eventService->getEventsByCategory($request['category']);
    }
    break;

  case 'search':
    if (!isset($request['search'])) {
      $response = ['success' => false, 'message' => 'Terme de recherche non fourni'];
    } else {
      $response = $eventService->searchEvents($request['search']);
    }
    break;

  case 'create':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $eventService->createEvent($request, $userId);
      }
    }
    break;

  case 'update':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $eventService->updateEvent((int) $request['id'], $request, $userId);
        }
      }
    }
    break;

  case 'delete':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $eventService->deleteEvent((int) $request['id'], $userId);
        }
      }
    }
    break;

  case 'getMyEvents':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $eventService->getEventsByUserId($userId);
      }
    }
    break;

  case 'requestModification':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request['event_id']) || !isset($request['new_date']) || !isset($request['new_time'])) {
          $response = ['success' => false, 'message' => 'Données incomplètes'];
        } else {
          $response = $eventModificationService->requestModification(
            (int) $request['event_id'], 
            $userId, 
            $request['new_date'], 
            $request['new_time']
          );
        }
      }
    }
    break;

  case 'requestDeletion':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        if (!isset($request['event_id']) || !isset($request['deletion_message'])) {
          $response = ['success' => false, 'message' => 'Données incomplètes'];
        } else {
          $response = $eventModificationService->requestDeletion(
            (int) $request['event_id'], 
            $userId, 
            $request['deletion_message']
          );
        }
      }
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
