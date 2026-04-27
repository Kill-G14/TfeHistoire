<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

// repositories 
use App\Repositories\PaymentRepository;
use App\Repositories\OrderRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\StripeService;

// repositories 
$paymentRepository = new PaymentRepository();
$orderRepository = new OrderRepository();
$orderItemRepository = new OrderItemRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$stripeService = new StripeService($paymentRepository, $orderRepository);

$request = json_decode(file_get_contents("php://input"), true);

// Vérifier que la requête est valide
if (!$request || !isset($request['action'])) {
    $response = ['success' => false, 'message' => 'Requête invalide'];
    echo json_encode($response);
    exit;
}

// Actions qui ne nécessitent pas d'authentification
$publicActions = ['getPublishableKey'];

// Vérifier le token pour les actions protégées
if (!in_array($request['action'], $publicActions)) {
    if (!isset($request['token'])) {
        $response = ['success' => false, 'message' => 'Token non fourni'];
        echo json_encode($response);
        exit;
    }

    $userId = $authService->checkToken($request['token']);
    if (!$userId) {
        $response = ['success' => false, 'message' => 'Token invalide'];
        echo json_encode($response);
        exit;
    }
}

switch ($request['action']) {
    case 'getPublishableKey':
        // Retourner la clé publique Stripe
        $config = require __DIR__ . '/../config.php';
        $response = [
            'success' => true,
            'data' => [
                'publishable_key' => $config['stripe']['publishable_key']
            ]
        ];
        break;

    case 'createCheckoutSession':
        // Créer une session de checkout Stripe
        if (!isset($request['order_id']) || !isset($request['order_items'])) {
            $response = ['success' => false, 'message' => 'Données manquantes'];
            break;
        }

        // Vérifier que la commande appartient à l'utilisateur
        $order = $orderRepository->getOrderById((int)$request['order_id']);
        if (!$order || $order->user_id !== $userId) {
            $response = ['success' => false, 'message' => 'Commande introuvable ou non autorisée'];
            break;
        }

        $response = $stripeService->createCheckoutSession(
            (int)$request['order_id'],
            $request['order_items']
        );
        break;

    case 'getPaymentStatus':
        // Récupérer le statut d'un paiement
        if (!isset($request['payment_id'])) {
            $response = ['success' => false, 'message' => 'Payment ID manquant'];
            break;
        }

        $payment = $paymentRepository->getPaymentById((int)$request['payment_id']);
        if (!$payment) {
            $response = ['success' => false, 'message' => 'Paiement introuvable'];
            break;
        }

        // Vérifier que la commande appartient à l'utilisateur
        $order = $orderRepository->getOrderById($payment->order_id);
        if (!$order || $order->user_id !== $userId) {
            $response = ['success' => false, 'message' => 'Non autorisé'];
            break;
        }

        $response = [
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'order_id' => $payment->order_id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'receipt_url' => $payment->receipt_url,
                'created_at' => $payment->created_at
            ]
        ];
        break;

    case 'getPaymentsByOrder':
        // Récupérer tous les paiements d'une commande
        if (!isset($request['order_id'])) {
            $response = ['success' => false, 'message' => 'Order ID manquant'];
            break;
        }

        // Vérifier que la commande appartient à l'utilisateur
        $order = $orderRepository->getOrderById((int)$request['order_id']);
        if (!$order || $order->user_id !== $userId) {
            $response = ['success' => false, 'message' => 'Commande introuvable ou non autorisée'];
            break;
        }

        $payments = $paymentRepository->getPaymentsByOrderId((int)$request['order_id']);
        
        $paymentsData = array_map(function($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'receipt_url' => $payment->receipt_url,
                'refund_amount' => $payment->refund_amount,
                'refunded_at' => $payment->refunded_at,
                'created_at' => $payment->created_at
            ];
        }, $payments);

        $response = [
            'success' => true,
            'data' => $paymentsData
        ];
        break;

    case 'requestRefund':
        // Demander un remboursement (admin ou organisateur)
        if (!isset($request['payment_id'])) {
            $response = ['success' => false, 'message' => 'Payment ID manquant'];
            break;
        }

        // Vérifier les droits de l'utilisateur
        $user = $userRepository->getUserById($userId);
        if (!$user || (!$user->is_admin && !$user->is_organizer)) {
            $response = ['success' => false, 'message' => 'Non autorisé - droits insuffisants'];
            break;
        }

        $amount = isset($request['amount']) ? (float)$request['amount'] : null;
        $response = $stripeService->createRefund((int)$request['payment_id'], $amount);
        break;

    default:
        $response = ['success' => false, 'message' => 'Action non reconnue'];
        break;
}

echo json_encode($response);
