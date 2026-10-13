<?php

// Webhook Stripe : appelé par Stripe (et non par le navigateur), pas de CORS ni de token.
// La seule preuve d'authenticité est la signature de la requête.

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
  exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

\App\Utils\EnvLoader::load();

use App\Repositories\EventRepository;
use App\Repositories\ReservationRepository;
use App\Repositories\UserRepository;
use App\Services\EmailService;
use App\Services\PaymentService;
use App\Utils\Logger;

$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
if ($webhookSecret === '') {
  Logger::error('Stripe webhook: STRIPE_WEBHOOK_SECRET manquant');
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Configuration manquante']);
  exit;
}

// Le corps brut est indispensable : la signature porte sur les octets exacts reçus
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
  $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);
} catch (\Throwable $exception) {
  // Signature invalide ou corps modifié : requête rejetée
  Logger::warning('Stripe webhook: signature invalide', ['error' => $exception->getMessage()]);
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Signature invalide']);
  exit;
}

$userRepository = new UserRepository();
$paymentService = new PaymentService(
  $userRepository,
  new EventRepository(),
  new ReservationRepository(),
  new EmailService($userRepository)
);

try {
  $paymentService->handleWebhookEvent($event);
} catch (\Throwable $exception) {
  // Erreur interne : on répond 500 pour que Stripe réessaie plus tard
  Logger::error('Stripe webhook: traitement en échec', [
    'event_id' => $event->id ?? null,
    'error' => $exception->getMessage(),
  ]);
  http_response_code(500);
  echo json_encode(['success' => false]);
  exit;
}

http_response_code(200);
echo json_encode(['success' => true]);
