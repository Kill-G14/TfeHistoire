<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// repositories 
use App\Repositories\UserRepository;
use App\Repositories\EventRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
use App\Validators\EventValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\UserService;
use App\Services\EventService;

// repositories 
$userRepository = new UserRepository();
$eventRepository = new EventRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
$eventValidator = new EventValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$userService = new UserService($userRepository);
$eventService = new EventService($eventRepository, $eventValidator);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['resource']) || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide - resource et action requis'];
  echo json_encode($response);
  exit;
}

// Vérifier l'authentification et les droits admin
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

// Vérifier que l'utilisateur est admin ou modérateur
$currentUser = $userRepository->getUserById($userId);
if (!$currentUser || (!$currentUser->is_admin && !$currentUser->is_moderator)) {
  $response = ['success' => false, 'message' => 'Accès non autorisé - Droits administrateur ou modérateur requis'];
  echo json_encode($response);
  exit;
}

$resource = $request['resource'];
$action = $request['action'];

// Router selon la ressource
switch ($resource) {

  // ============================================
  // GESTION DES ÉVÉNEMENTS
  // ============================================
  case 'events':
    switch ($action) {

      case 'getAll':
        $response = $eventService->getAllEvents();
        break;

      case 'getPending':
        $response = $eventService->getPendingEvents();
        break;

      case 'approve':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          // Vérifier que l'utilisateur est admin (pas seulement modérateur)
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventService->approveEvent((int) $request['id']);
          }
        }
        break;

      case 'reject':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          // Vérifier que l'utilisateur est admin (pas seulement modérateur)
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventService->rejectEvent((int) $request['id']);
          }
        }
        break;

      case 'delete':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          // Vérifier que l'utilisateur est admin (pas seulement modérateur)
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventService->adminDeleteEvent((int) $request['id']);
          }
        }
        break;

      default:
        $response = ['success' => false, 'message' => 'Action non reconnue pour la ressource events'];
        break;
    }
    break;

  // ============================================
  // GESTION DES UTILISATEURS
  // ============================================
  case 'users':
    // Vérifier que l'utilisateur est admin (pas seulement modérateur)
    if (!$currentUser->is_admin) {
      $response = ['success' => false, 'message' => 'Droits administrateur requis pour gérer les utilisateurs'];
      break;
    }

    switch ($action) {

      case 'getAll':
        $response = $userService->getAllUsers();
        break;

      case 'getById':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $userService->getUserById((int) $request['id']);
        }
        break;

      case 'updateRoles':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $isAdmin = isset($request['is_admin']) ? (bool) $request['is_admin'] : false;
          $isOrganizer = isset($request['is_organizer']) ? (bool) $request['is_organizer'] : false;
          $isModerator = isset($request['is_moderator']) ? (bool) $request['is_moderator'] : false;
          
          $response = $userService->updateUserRoles((int) $request['id'], $isAdmin, $isOrganizer, $isModerator);
        }
        break;

      case 'delete':
        if (!isset($request['id'])) {
          $response = ['success' => false, 'message' => 'ID non fourni'];
        } else {
          $response = $userService->adminDeleteUser((int) $request['id']);
        }
        break;

      default:
        $response = ['success' => false, 'message' => 'Action non reconnue pour la ressource users'];
        break;
    }
    break;

  // ============================================
  // RESSOURCE NON RECONNUE
  // ============================================
  default:
    $response = ['success' => false, 'message' => 'Ressource non reconnue'];
    break;
}

echo json_encode($response);
