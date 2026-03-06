<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;

// Models
// repositories 
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);

$request = json_decode(file_get_contents("php://input"));

// Vérifier que la requête est valide
if (!$request || !isset($request->action)) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request->action) {
  case 'register':
    $response = $authService->register((array) $request);
    break;

  case 'login':
    $response = $authService->login((array) $request);
    break;

  case 'getCurrentUser':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request->token);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $authService->getCurrentUser($userId);
      }
    }
    break;

  case 'logout':
    if (!isset($request->token)) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $response = $authService->logout($request->token);
    }
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
