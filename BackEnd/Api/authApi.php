<?php

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
\App\Utils\EnvLoader::load();

// Models
// repositories 
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Repositories\PasswordResetRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\EmailService;
use App\Services\PasswordResetService;
// Utils
use App\Utils\RateLimiter;

// Models
// repositories 
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
$passwordResetRepository = new PasswordResetRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$emailService = new EmailService($userRepository);
$passwordResetService = new PasswordResetService($passwordResetRepository, $userRepository, $emailService);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
  $response = ['success' => false, 'message' => 'Requête invalide'];
  echo json_encode($response);
  exit;
}

switch ($request['action']) {
  case 'register':
    // RATE LIMITING : Limiter les tentatives d'inscription (protection contre spam/bots)
    $email = $request['email'] ?? '';
    $checkLimit = RateLimiter::check('register', $email);
    
    if (!$checkLimit['allowed']) {
      $response = [
        'success' => false, 
        'message' => $checkLimit['message'] ?? 'Trop de tentatives'
      ];
      break;
    }
    
    // Traiter l'inscription
    $response = $authService->register($request);
    
    // Si inscription échouée, enregistrer la tentative
    if (!$response['success']) {
      RateLimiter::recordAttempt('register', $email);
    } else {
      // Si inscription réussie, réinitialiser le compteur
      RateLimiter::reset('register', $email);
    }
    break;

  case 'login':
    // RATE LIMITING : Protection contre brute force sur le login
    $email = $request['email'] ?? '';
    $checkLimit = RateLimiter::check('login', $email);
    
    if (!$checkLimit['allowed']) {
      $response = [
        'success' => false, 
        'message' => $checkLimit['message'] ?? 'Trop de tentatives de connexion'
      ];
      break;
    }
    
    // Traiter la connexion
    $response = $authService->login($request);
    
    // Si login échoué, enregistrer la tentative
    if (!$response['success']) {
      RateLimiter::recordAttempt('login', $email);
    } else {
      // Si login réussi, réinitialiser le compteur
      RateLimiter::reset('login', $email);
    }
    break;

  case 'getCurrentUser':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $authService->getCurrentUser($userId);
      }
    }
    break;

  case 'logout':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $response = $authService->logout($request['token']);
    }
    break;

  case 'changePassword':
    if (!isset($request['token'])) {
      $response = ['success' => false, 'message' => 'Token non fourni'];
    } else {
      $userId = $authService->checkToken($request['token']);
      if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
      } else {
        $response = $authService->changePassword($userId, $request);
      }
    }
    break;

  case 'requestPasswordReset':
    // RATE LIMITING : Protection contre les abus de reset de mot de passe
    $email = $request['email'] ?? '';
    $checkLimit = RateLimiter::check('password_reset', $email);
    
    if (!$checkLimit['allowed']) {
      $response = [
        'success' => false, 
        'message' => $checkLimit['message'] ?? 'Trop de tentatives'
      ];
      break;
    }
    
    $response = $passwordResetService->requestPasswordReset($email);
    
    // Enregistrer la tentative même en cas de succès (pour éviter l'énumération)
    RateLimiter::recordAttempt('password_reset', $email);
    break;

  case 'resetPassword':
    if (!isset($request['email']) || !isset($request['code']) || !isset($request['newPassword'])) {
      $response = ['success' => false, 'message' => 'Données manquantes'];
      break;
    }

    $response = $passwordResetService->resetPassword(
      $request['email'],
      $request['code'],
      $request['newPassword']
    );
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
