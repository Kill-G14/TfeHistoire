<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Gestion de la requête OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Utils\Logger;

try {
  // Instanciation des dépendances
  $userRepository = new UserRepository();
  $authService = new AuthService($userRepository);

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

  // Routing par action
  switch ($action) {
    case 'register':
      $result = $authService->register($data);
      echo json_encode($result);
      break;

    case 'login':
      $result = $authService->login($data);
      echo json_encode($result);
      break;

    case 'getCurrentUser':
      // Récupérer le token depuis les headers
      $headers = getallheaders();
      $token = $headers['Authorization'] ?? null;

      if (!$token) {
        echo json_encode([
          'success' => false,
          'message' => 'Token non fourni'
        ]);
        exit;
      }

      // Retirer "Bearer " du token
      $token = str_replace('Bearer ', '', $token);

      // Vérifier le token
      $userId = $authService->verifyToken($token);

      if (!$userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Token invalide'
        ]);
        exit;
      }

      $result = $authService->getCurrentUser($userId);
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
  Logger::error('Auth API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
