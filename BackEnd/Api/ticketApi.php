<?php

// Headers CORS
require_once __DIR__ . '/cors.php';
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

// Imports
use App\Repositories\EventRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;
use App\Services\SessionService;
use App\Services\TicketService;

// Réponse JSON d'erreur (le PDF, lui, est renvoyé en binaire)
function sendJsonError(string $message, int $status): void {
  http_response_code($status);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode(['success' => false, 'message' => $message]);
  exit;
}

// Instances
$sessionService = new SessionService(new SessionRepository());
$ticketService = new TicketService(
  new ReservationRepository(),
  new EventRepository(),
  new UserRepository()
);

// Récupération des données de la requête
$request = json_decode(file_get_contents("php://input"), true);

if (!$request || !isset($request['action'])) {
  sendJsonError('Requête invalide', 400);
}

// Vérification d'un billet : publique (page ouverte en scannant le QR code)
if ($request['action'] === 'verifyTicket') {
  try {
    $result = $ticketService->verifyTicket((string) ($request['code'] ?? ''));
  } catch (\Throwable $exception) {
    \App\Utils\Logger::error('Erreur vérification billet', ['error' => $exception->getMessage()]);
    sendJsonError('Vérification impossible', 500);
  }
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($result);
  exit;
}

// Les autres actions ne sont jamais publiques : utilisateur connecté obligatoire
$token = $request['token'] ?? '';
$userId = $token ? $sessionService->getUserIdByToken($token) : null;

if (!$userId) {
  sendJsonError('Token invalide ou expiré', 401);
}

$reservationId = (int) ($request['reservation_id'] ?? 0);

try {
  switch ($request['action']) {
    case 'getTickets':
      $response = $ticketService->getTickets($userId, $reservationId);
      if (!$response['success']) {
        sendJsonError($response['message'], $response['status'] ?? 400);
      }
      header("Content-Type: application/json; charset=UTF-8");
      echo json_encode($response);
      break;

    case 'downloadPdf':
      $result = $ticketService->generatePdf($userId, $reservationId);
      if (!$result['success']) {
        sendJsonError($result['message'], $result['status'] ?? 400);
      }
      header("Content-Type: application/pdf");
      header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
      header("Content-Length: " . strlen($result['pdf']));
      echo $result['pdf'];
      break;

    default:
      sendJsonError('Action non reconnue', 400);
  }
} catch (\Throwable $exception) {
  // Détail dans les logs, jamais renvoyé au navigateur
  \App\Utils\Logger::error('Erreur billets', ['error' => $exception->getMessage()]);
  sendJsonError('Impossible de générer les billets', 500);
}
