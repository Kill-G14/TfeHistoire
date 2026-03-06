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

$request = json_decode(file_get_contents("php://input"));

switch ($request->action) {
  case 'getByEvent':
    $response = $ticketService->getTicketsByEventId((int) $request->event_id);
    break;

  case 'getById':
    $response = $ticketService->getTicketById((int) $request->id);
    break;

  case 'create':
    $userId = $authService->checkToken($request->token);
    if (!$userId) {
      $response = ['success' => false, 'message' => 'Token invalide'];
    } else {
      $response = $ticketService->createTicket((array) $request, $userId);
    }
    break;

  case 'update':
    $userId = $authService->checkToken($request->token);
    if (!$userId) {
      $response = ['success' => false, 'message' => 'Token invalide'];
    } else {
      $response = $ticketService->updateTicket((int) $request->id, (array) $request, $userId);
    }
    break;

  case 'delete':
    $userId = $authService->checkToken($request->token);
    if (!$userId) {
      $response = ['success' => false, 'message' => 'Token invalide'];
    } else {
      $response = $ticketService->deleteTicket((int) $request->id, $userId);
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
