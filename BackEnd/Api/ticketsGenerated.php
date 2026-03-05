<?php

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Gestion de la requête OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

// Autoload Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Repositories
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\OrderRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;

// Validators
use App\Validators\UserValidator;

// Services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\PdfService;

// Utils
use App\Utils\Logger;

// Repositories
$purchasedTicketRepository = new PurchasedTicketRepository();
$orderRepository = new OrderRepository();
$eventRepository = new EventRepository();
$ticketRepository = new TicketRepository();
$orderItemRepository = new OrderItemRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();

// Validators
$userValidator = new UserValidator();

// Services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$pdfService = new PdfService($eventRepository, $ticketRepository, $orderItemRepository);

try {
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

  // Vérifier l'authentification
  if (!isset($data['token']) || empty($data['token'])) {
    echo json_encode([
      'success' => false,
      'message' => 'Non authentifié'
    ]);
    exit;
  }

  $token = $data['token'];
  $userId = $authService->checkToken($token);

  if (!$userId) {
    echo json_encode([
      'success' => false,
      'message' => 'Token invalide'
    ]);
    exit;
  }

  $action = $data['action'];

  // Routing par action
  switch ($action) {
    case 'getMyTickets':
      $tickets = $purchasedTicketRepository->getTicketsByUserId($userId);
      
      $ticketDTOs = array_map(function($ticket) {
        return [
          'id' => $ticket->id,
          'order_item_id' => $ticket->order_item_id,
          'qr_code' => $ticket->qr_code,
          'unique_code' => $ticket->unique_code,
          'is_used' => $ticket->is_used,
          'used_at' => $ticket->used_at ?? null,
          'created_at' => $ticket->created_at
        ];
      }, $tickets);

      echo json_encode([
        'success' => true,
        'data' => $ticketDTOs
      ]);
      break;

    case 'getTicketById':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      $ticket = $purchasedTicketRepository->getTicketById((int) $data['id']);

      if (!$ticket) {
        echo json_encode([
          'success' => false,
          'message' => 'Billet non trouvé'
        ]);
        exit;
      }

      // Vérifier que l'utilisateur possède ce billet
      // TODO: Ajouter une méthode pour vérifier la propriété du billet via order_item -> order -> user_id

      echo json_encode([
        'success' => true,
        'data' => [
          'id' => $ticket->id,
          'order_item_id' => $ticket->order_item_id,
          'qr_code' => $ticket->qr_code,
          'unique_code' => $ticket->unique_code,
          'is_used' => $ticket->is_used,
          'used_at' => $ticket->used_at ?? null,
          'created_at' => $ticket->created_at
        ]
      ]);
      break;

    case 'downloadTicket':
      if (!isset($data['id'])) {
        echo json_encode([
          'success' => false,
          'message' => 'ID non fourni'
        ]);
        exit;
      }

      // Récupérer le billet
      $ticketGenerated = $purchasedTicketRepository->getTicketById((int) $data['id']);

      if (!$ticketGenerated) {
        echo json_encode([
          'success' => false,
          'message' => 'Billet non trouvé'
        ]);
        exit;
      }

      // Vérifier que l'utilisateur possède bien ce billet
      $orderItem = $orderItemRepository->getOrderItemById($ticketGenerated->order_item_id);
      if (!$orderItem) {
        echo json_encode([
          'success' => false,
          'message' => 'Commande introuvable'
        ]);
        exit;
      }

      $order = $orderRepository->getOrderById($orderItem->order_id);
      if (!$order || $order->user_id !== $userId) {
        echo json_encode([
          'success' => false,
          'message' => 'Accès non autorisé à ce billet'
        ]);
        exit;
      }

      // Générer le PDF via PdfService
      $result = $pdfService->generateTicketPdf($ticketGenerated, $userId);

      if (!$result['success']) {
        echo json_encode($result);
        exit;
      }

      // Envoyer le PDF au client
      $pdfPath = $result['data']['pdf_path'];
      $filename = $result['data']['filename'];

      if (!file_exists($pdfPath)) {
        echo json_encode([
          'success' => false,
          'message' => 'Fichier PDF introuvable'
        ]);
        exit;
      }

      // Modifier les headers pour l'envoi du fichier
      header('Content-Type: application/pdf');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      header('Content-Length: ' . filesize($pdfPath));
      header('Cache-Control: private, max-age=0, must-revalidate');
      header('Pragma: public');

      // Nettoyer le buffer de sortie et envoyer le fichier
      ob_clean();
      flush();
      readfile($pdfPath);

      // Optionnel : Supprimer le fichier après envoi pour économiser l'espace
      // unlink($pdfPath);

      exit;
      break;

    default:
      echo json_encode([
        'success' => false,
        'message' => 'Action inconnue'
      ]);
      break;
  }

} catch (Exception $e) {
  Logger::error('Generated Tickets API error', ['error' => $e->getMessage()]);
  echo json_encode([
    'success' => false,
    'message' => 'Une erreur est survenue'
  ]);
}
