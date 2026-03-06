<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\FavoriteRepository;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\FavoriteService;
use App\Services\SessionService;

// Models
// repositories 
$favoriteRepository = new FavoriteRepository();
$eventRepository = new EventRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$favoriteService = new FavoriteService($favoriteRepository, $eventRepository);

$request = json_decode(file_get_contents("php://input"));

$userId = $authService->checkToken($request->token);
if (!$userId) {
  $response = ['success' => false, 'message' => 'Token invalide'];
  echo json_encode($response);
  exit;
}

switch ($request->action) {
  case 'getMyFavorites':
    $response = $favoriteService->getUserFavorites($userId);
    break;

  case 'add':
    $response = $favoriteService->addFavorite($userId, (int) $request->event_id);
    break;

  case 'remove':
    $response = $favoriteService->removeFavorite($userId, (int) $request->event_id);
    break;

  case 'isFavorite':
    $response = $favoriteService->isFavorite($userId, (int) $request->event_id);
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
