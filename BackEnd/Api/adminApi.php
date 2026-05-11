<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// repositories 
use App\Repositories\UserRepository;
use App\Repositories\EventRepository;
use App\Repositories\EventModificationRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
use App\Validators\EventValidator;
use App\Validators\EventModificationValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\UserService;
use App\Services\EventService;
use App\Services\EventModificationService;
use App\Services\EmailService;

// repositories 
$userRepository = new UserRepository();
$eventRepository = new EventRepository();
$eventModificationRepository = new EventModificationRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
$eventValidator = new EventValidator();
$eventModificationValidator = new EventModificationValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$userService = new UserService($userRepository);
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
        $response = $eventService->getAllEventsForAdmin();
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

      case 'getPendingModifications':
        // Récupérer toutes les modifications en attente
        $response = $eventModificationService->getPendingModifications();
        break;

      case 'approveModification':
        if (!isset($request['modification_id'])) {
          $response = ['success' => false, 'message' => 'ID de modification non fourni'];
        } else {
          // Vérifier que l'utilisateur est admin
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventModificationService->approveModification((int) $request['modification_id']);
          }
        }
        break;

      case 'rejectModification':
        if (!isset($request['modification_id']) || !isset($request['reason'])) {
          $response = ['success' => false, 'message' => 'Données incomplètes'];
        } else {
          // Vérifier que l'utilisateur est admin
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventModificationService->rejectModification(
              (int) $request['modification_id'], 
              $request['reason']
            );
          }
        }
        break;

      case 'getPendingDeletions':
        // Récupérer tous les événements en attente de suppression
        $response = $eventModificationService->getPendingDeletions();
        break;

      case 'approveDeletion':
        if (!isset($request['event_id'])) {
          $response = ['success' => false, 'message' => 'ID d\'événement non fourni'];
        } else {
          // Vérifier que l'utilisateur est admin
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $adminMessage = $request['admin_message'] ?? null;
            $response = $eventModificationService->approveDeletion(
              (int) $request['event_id'], 
              $adminMessage
            );
          }
        }
        break;

      case 'rejectDeletion':
        if (!isset($request['event_id']) || !isset($request['reason'])) {
          $response = ['success' => false, 'message' => 'Données incomplètes'];
        } else {
          // Vérifier que l'utilisateur est admin
          if (!$currentUser->is_admin) {
            $response = ['success' => false, 'message' => 'Droits administrateur requis'];
          } else {
            $response = $eventModificationService->rejectDeletion(
              (int) $request['event_id'], 
              $request['reason']
            );
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
