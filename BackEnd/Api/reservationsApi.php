<?php

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Imports
use App\Repositories\ReservationRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Services\ReservationService;
use App\Services\SessionService;

// Instances
$reservationRepository = new ReservationRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
$sessionService = new SessionService($sessionRepository);
$reservationService = new ReservationService(
    $reservationRepository,
    $eventRepository,
    $userRepository
);

// Récupération des données
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Créer une réservation
            $token = $data['token'] ?? '';
            $eventId = $data['event_id'] ?? 0;
            $quantity = $data['quantity'] ?? 1;

            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token manquant'
                ]);
                exit;
            }

            // Valider le token
            $userId = $sessionService->getUserIdByToken($token);
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide ou expir\u00e9'
                ]);
                exit;
            }

            $result = $reservationService->createReservation($userId, $eventId, $quantity);
            echo json_encode($result);
            break;

        case 'getMyReservations':
            // Récupérer les réservations de l'utilisateur
            $token = $data['token'] ?? '';

            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token manquant'
                ]);
                exit;
            }

            // Valider le token
            $userId = $sessionService->getUserIdByToken($token);
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide ou expir\u00e9'
                ]);
                exit;
            }

            $result = $reservationService->getUserReservations($userId);
            echo json_encode($result);
            break;

        case 'cancel':
            // Annuler une réservation
            $token = $data['token'] ?? '';
            $reservationId = $data['reservation_id'] ?? 0;

            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token manquant'
                ]);
                exit;
            }

            // Valider le token
            $userId = $sessionService->getUserIdByToken($token);
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide ou expir\u00e9'
                ]);
                exit;
            }

            $result = $reservationService->cancelReservation($reservationId, $userId);
            echo json_encode($result);
            break;

        case 'getAvailableTickets':
            // Récupérer le nombre de places disponibles
            $eventId = $data['event_id'] ?? 0;

            $result = $reservationService->getAvailableTickets($eventId);
            echo json_encode($result);
            break;

        case 'checkReservation':
            // Vérifier si l'utilisateur a déjà réservé
            $token = $data['token'] ?? '';
            $eventId = $data['event_id'] ?? 0;

            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token manquant'
                ]);
                exit;
            }

            // Valider le token
            $userId = $sessionService->getUserIdByToken($token);
            if (!$userId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token invalide ou expir\u00e9'
                ]);
                exit;
            }

            $hasReservation = $reservationRepository->hasReservation($userId, $eventId);

            echo json_encode([
                'success' => true,
                'data' => [
                    'has_reservation' => $hasReservation
                ]
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
