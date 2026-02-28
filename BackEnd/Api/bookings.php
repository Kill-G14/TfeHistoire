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

use App\Services\BookingService;
use App\Services\AuthService;
use App\Repositories\BookingRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Utils\Logger;

try {
  // Instanciation des dépendances
  $bookingRepository = new BookingRepository();
  $eventRepository = new EventRepository();
  $bookingService = new BookingService($bookingRepository, $eventRepository);
  $userRepository = new UserRepository();
  $authService = new AuthService($userRepository);

  // Vérifier l'authentification
  $headers = getallheaders();
  $token = $headers['Authorization'] ?? null;

  if (!$token) {
    echo json_encode([
      'success' => false,
      'message' => 'Non authentifié'
    ]);
    exit;
  }

  $token = str_replace('Bearer ', '', $token);
  $userId = $authService->verifyToken($token);

  if (!$userId) {
    echo json_encode([
      'success' => false,
      'message' => 'Token invalide'
    ]);
    exit;
  }

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
    case 'getMyBookings':
      $result = $bookingService->getUserBookings($userId);
      echo json_encode($result);
      break;

    case 'create':
      $result = $bookingService->createBooking($data, $userId);
      echo json_encode($result);
      break;

    case 'cancel':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $result = $bookingService->cancelBooking((int) $data['id'], $userId);
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
  Logger::error('Bookings API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
