<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;

// Validators
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\SessionService;

// Utils
use App\Utils\Logger;

// Repositories
$purchasedTicketRepository = new PurchasedTicketRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);

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

  // Vérifier l'authentification (organizer)
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

  // Vérifier que l'utilisateur est un organizer
  $user = $userRepository->getUserById($userId);
  if (!$user || !$user->is_organizer) {
    echo json_encode([
      'success' => false,
      'message' => 'Accès refusé. Vous devez être un organisateur.'
    ]);
    exit;
  }

  $action = $data['action'];

  // Routing par action
  switch ($action) {
    case 'validate':
      if (!isset($data['unique_code']) && !isset($data['qr_code'])) {
        echo json_encode([
          'success' => false,
          'message' => 'Code unique ou QR code requis'
        ]);
        exit;
      }

      $code = $data['unique_code'] ?? $data['qr_code'];

      // Récupérer le billet par code
      $ticket = $purchasedTicketRepository->getTicketByUniqueCode($code);

      if (!$ticket) {
        echo json_encode([
          'success' => false,
          'message' => 'Billet non trouvé'
        ]);
        exit;
      }

      // Vérifier si le billet est déjà utilisé
      if ($ticket->is_used) {
        echo json_encode([
          'success' => false,
          'message' => 'Ce billet a déjà été utilisé',
          'data' => [
            'used_at' => $ticket->used_at
          ]
        ]);
        exit;
      }

      // Marquer le billet comme utilisé
      $success = $purchasedTicketRepository->markTicketAsUsed($ticket->id);

      if (!$success) {
        Logger::error('Failed to mark ticket as used', ['ticket_id' => $ticket->id]);
        echo json_encode([
          'success' => false,
          'message' => 'Erreur lors de la validation du billet'
        ]);
        exit;
      }

      Logger::info('Ticket validated successfully', ['ticket_id' => $ticket->id, 'organizer_id' => $userId]);

      echo json_encode([
        'success' => true,
        'message' => 'Billet validé avec succès',
        'data' => [
          'id' => $ticket->id,
          'unique_code' => $ticket->unique_code,
          'validated_at' => date('Y-m-d H:i:s')
        ]
      ]);
      break;

    case 'checkStatus':
      if (!isset($data['unique_code']) && !isset($data['qr_code'])) {
        echo json_encode([
          'success' => false,
          'message' => 'Code unique ou QR code requis'
        ]);
        exit;
      }

      $code = $data['unique_code'] ?? $data['qr_code'];

      // Récupérer le billet par code
      $ticket = $purchasedTicketRepository->getTicketByUniqueCode($code);

      if (!$ticket) {
        echo json_encode([
          'success' => false,
          'message' => 'Billet non trouvé'
        ]);
        exit;
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'id' => $ticket->id,
          'unique_code' => $ticket->unique_code,
          'is_used' => $ticket->is_used,
          'used_at' => $ticket->used_at ?? null,
          'created_at' => $ticket->created_at
        ]
      ]);
      break;

    default:
      echo json_encode([
        'success' => false,
        'message' => 'Action inconnue'
      ]);
      break;
  }

} catch (Exception $e) {
  Logger::error('Scan Ticket API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
