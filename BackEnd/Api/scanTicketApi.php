<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;

// Models
// repositories 
$purchasedTicketRepository = new PurchasedTicketRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

// Vérifier le token
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

// Vérifier que l'utilisateur est un organizer
$user = $userRepository->getUserById($userId);
if (!$user || !$user->is_organizer) {
  $response = ['success' => false, 'message' => 'Accès refusé. Vous devez être un organisateur.'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'validate':
    if (!isset($request['unique_code']) && !isset($request['qr_code'])) {
      $response = ['success' => false, 'message' => 'Code unique ou QR code requis'];
      break;
    }
    $code = $request['unique_code'] ?? $request['qr_code'];
    $ticket = $purchasedTicketRepository->getTicketByUniqueCode($code);

    if (!$ticket) {
      $response = ['success' => false, 'message' => 'Billet non trouvé'];
    } elseif ($ticket->is_used) {
      $response = [
        'success' => false, 
        'message' => 'Ce billet a déjà été utilisé',
        'data' => ['used_at' => $ticket->used_at]
      ];
    } else {
      $success = $purchasedTicketRepository->markTicketAsUsed($ticket->id);
      if (!$success) {
        $response = ['success' => false, 'message' => 'Erreur lors de la validation du billet'];
      } else {
        $response = [
          'success' => true,
          'message' => 'Billet validé avec succès',
          'data' => [
            'id' => $ticket->id,
            'unique_code' => $ticket->unique_code,
            'validated_at' => date('Y-m-d H:i:s')
          ]
        ];
      }
    }
    break;

  case 'checkStatus':
    if (!isset($request['unique_code']) && !isset($request['qr_code'])) {
      $response = ['success' => false, 'message' => 'Code unique ou QR code requis'];
      break;
    }
    $code = $request['unique_code'] ?? $request['qr_code'];
    $ticket = $purchasedTicketRepository->getTicketByUniqueCode($code);

    if (!$ticket) {
      $response = ['success' => false, 'message' => 'Billet non trouvé'];
    } else {
      $response = [
        'success' => true,
        'data' => [
          'id' => $ticket->id,
          'unique_code' => $ticket->unique_code,
          'is_used' => $ticket->is_used,
          'used_at' => $ticket->used_at ?? null,
          'created_at' => $ticket->created_at
        ]
      ];
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
