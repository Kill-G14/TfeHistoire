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
use App\Repositories\TicketRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;

// Validators
use App\Validators\TicketValidator;
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\TicketService;
use App\Services\SessionService;

// Utils
use App\Utils\Logger;

// Repositories
$ticketRepository = new TicketRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$ticketValidator = new TicketValidator();
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$ticketService = new TicketService($ticketRepository, $eventRepository, $ticketValidator);

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

  // Actions publiques (pas besoin d'authentification)
  if ($action === 'getByEvent') {
    if (!isset($data['event_id'])) {
      echo json_encode([
        'success' => false,
        'message' => 'ID de l\'événement non fourni'
      ]);
      exit;
    }

    $result = $ticketService->getTicketsByEventId((int) $data['event_id']);
    echo json_encode($result);
    exit;
  }

  // Actions nécessitant l'authentification (organizer)
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

  // Routing par action
  switch ($action) {
    case 'create':
      $result = $ticketService->createTicket($data, $userId);
      echo json_encode($result);
      break;

    case 'update':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $ticketService->updateTicket((int) $data['id'], $data, $userId);
      echo json_encode($result);
      break;

    case 'delete':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $ticketService->deleteTicket((int) $data['id'], $userId);
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

      $result = $ticketService->getTicketById((int) $data['id']);
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
  Logger::error('Tickets API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
