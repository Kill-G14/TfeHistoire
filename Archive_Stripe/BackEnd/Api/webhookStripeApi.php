<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Stripe-Signature");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

// repositories 
use App\Repositories\PaymentRepository;
use App\Repositories\OrderRepository;
// services
use App\Services\StripeService;
use App\Utils\Logger;

// repositories 
$paymentRepository = new PaymentRepository();
$orderRepository = new OrderRepository();
// services
$stripeService = new StripeService($paymentRepository, $orderRepository);

// Récupérer le payload brut
$payload = @file_get_contents('php://input');

// Récupérer la signature Stripe
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload)) {
    Logger::error('Webhook Stripe : payload vide');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload vide']);
    exit;
}

if (empty($signature)) {
    Logger::error('Webhook Stripe : signature manquante');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Signature manquante']);
    exit;
}

// Logger la réception du webhook
Logger::info('Webhook Stripe reçu - Signature: ' . substr($signature, 0, 20) . '...');

try {
    // Traiter le webhook
    $response = $stripeService->handleWebhook($payload, $signature);

    if ($response['success']) {
        Logger::info('Webhook Stripe traité avec succès : ' . $response['message']);
        http_response_code(200);
        echo json_encode($response);
    } else {
        Logger::error('Erreur webhook Stripe : ' . $response['message']);
        http_response_code(400);
        echo json_encode($response);
    }

} catch (\Exception $e) {
    Logger::error('Exception webhook Stripe : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
