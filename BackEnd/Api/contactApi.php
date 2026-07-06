<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

// repositories
use App\Repositories\ContactMessageRepository;
// Validator
use App\Validators\ContactValidator;
// services
use App\Services\ContactService;
// Utils
use App\Utils\RateLimiter;

// repositories
$contactMessageRepository = new ContactMessageRepository();
// Validator
$contactValidator = new ContactValidator();
// services
$contactService = new ContactService($contactMessageRepository, $contactValidator);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'send':
    // RATE LIMITING : Protection contre le spam sur le formulaire de contact
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $checkLimit = RateLimiter::check('contact', $ip);

    if (!$checkLimit['allowed']) {
      $response = [
        'success' => false,
        'message' => $checkLimit['message'] ?? 'Trop de tentatives'
      ];
      break;
    }

    $response = $contactService->submitMessage($request);

    // Chaque soumission compte dans la limite, valide ou non (protection anti-spam)
    RateLimiter::recordAttempt('contact', $ip);
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
