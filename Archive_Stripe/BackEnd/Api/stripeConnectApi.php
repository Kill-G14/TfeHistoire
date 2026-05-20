<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Validators\UserValidator;
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\StripeConnectService;

// Repositories
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$stripeConnectService = new StripeConnectService($userRepository);

$request = json_decode(file_get_contents("php://input"), true);

if (!$request || !isset($request['action'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

// Vérifier le token
if (!isset($request['token'])) {
    echo json_encode(['success' => false, 'message' => 'Token non fourni']);
    exit;
}

$userId = $authService->checkToken($request['token']);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Token invalide']);
    exit;
}

switch ($request['action']) {
    case 'checkStripeAccount':
        // Vérifier si l'utilisateur a un compte Stripe connecté
        $stripeData = $userRepository->getStripeAccountStatus($userId);
        
        $hasAccount = $stripeData && 
                     !empty($stripeData['stripe_account_id']) && 
                     $stripeData['stripe_onboarding_completed'];
        
        $response = [
            'success' => true,
            'data' => [
                'has_stripe_account' => $hasAccount,
                'status' => $stripeData['stripe_account_status'] ?? 'not_connected',
                'stripe_account_id' => $stripeData['stripe_account_id'] ?? null,
                'stripe_connected_at' => $stripeData['stripe_connected_at'] ?? null
            ]
        ];
        break;
    
    case 'createConnectAccount':
        // Créer un compte Stripe Connect et obtenir le lien d'onboarding
        $user = $userRepository->getUserById($userId);
        
        if (!$user) {
            $response = ['success' => false, 'message' => 'Utilisateur introuvable'];
            break;
        }
        
        $response = $stripeConnectService->createConnectAccount($userId, $user->email);
        break;
    
    case 'verifyAccountCompletion':
        // Vérifier si l'onboarding Stripe est terminé
        $stripeData = $userRepository->getStripeAccountStatus($userId);
        
        if (!$stripeData || empty($stripeData['stripe_account_id'])) {
            $response = ['success' => false, 'message' => 'Aucun compte Stripe trouvé'];
            break;
        }
        
        $statusResult = $stripeConnectService->checkAccountStatus($stripeData['stripe_account_id']);
        
        if ($statusResult['success'] && $statusResult['data']['is_complete']) {
            // Mettre à jour le statut dans la BDD
            $userRepository->updateStripeAccount(
                $userId,
                $stripeData['stripe_account_id'],
                'connected',
                true
            );
            
            $response = [
                'success' => true,
                'message' => 'Compte Stripe vérifié et connecté',
                'data' => [
                    'is_complete' => true,
                    'charges_enabled' => $statusResult['data']['charges_enabled'],
                    'payouts_enabled' => $statusResult['data']['payouts_enabled']
                ]
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Onboarding non terminé',
                'data' => [
                    'is_complete' => false,
                    'details_submitted' => $statusResult['data']['details_submitted'] ?? false
                ]
            ];
        }
        break;
    
    case 'getDashboardLink':
        // Créer un lien vers le dashboard Stripe du créateur
        $stripeData = $userRepository->getStripeAccountStatus($userId);
        
        if (!$stripeData || empty($stripeData['stripe_account_id'])) {
            $response = ['success' => false, 'message' => 'Aucun compte Stripe trouvé'];
            break;
        }
        
        $response = $stripeConnectService->createDashboardLink($stripeData['stripe_account_id']);
        break;
    
    default:
        $response = ['success' => false, 'message' => 'Action non reconnue'];
        break;
}

echo json_encode($response);
