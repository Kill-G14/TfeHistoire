<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\TicketRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\TicketValidator;
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\TicketService;
use App\Services\SessionService;

// Models
// repositories 
$ticketRepository = new TicketRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$ticketValidator = new TicketValidator();
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$ticketService = new TicketService($ticketRepository, $eventRepository, $ticketValidator);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'getByEvent':
    if (!isset($request['event_id'])) {
      $response = ['success' => false, 'message' => 'ID de l\'événement non fourni'];
    } else {
      $response = $ticketService->getTicketsByEventId((int) $request['event_id']);
    }
    break;

  case 'getById':
    if (!isset($request['id'])) {
      $response = ['success' => false, 'message' => 'ID non fourni'];
    } else {
      $response = $ticketService->getTicketById((int) $request['id']);
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
        $response = $ticketService->createTicket($request, $userId);
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
          $response = $ticketService->updateTicket((int) $request['id'], $request, $userId);
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
          $response = $ticketService->deleteTicket((int) $request['id'], $userId);
        }
      }
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
