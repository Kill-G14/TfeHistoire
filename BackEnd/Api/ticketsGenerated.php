<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../vendor/autoload.php';

// Models
// repositories 
use App\Repositories\PurchasedTicketRepository;
use App\Repositories\OrderRepository;
use App\Repositories\EventRepository;
use App\Repositories\TicketRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
// Validator
use App\Validators\UserValidator;
// services
use App\Services\AuthService;
use App\Services\SessionService;
use App\Services\PdfService;

// Models
// repositories 
$purchasedTicketRepository = new PurchasedTicketRepository();
$orderRepository = new OrderRepository();
$eventRepository = new EventRepository();
$ticketRepository = new TicketRepository();
$orderItemRepository = new OrderItemRepository();
$userRepository = new UserRepository();
$sessionRepository = new SessionRepository();
// Validator
$userValidator = new UserValidator();
// services
$sessionService = new SessionService($sessionRepository);
$authService = new AuthService($userRepository, $userValidator, $sessionService);
$pdfService = new PdfService($eventRepository, $ticketRepository, $orderItemRepository);

$request = json_decode(file_get_contents("php://input"));

$userId = $authService->checkToken($request->token);
if (!$userId) {
  $response = ['success' => false, 'message' => 'Token invalide'];
  echo json_encode($response);
  exit;
}

switch ($request->action) {
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

    $response = ['success' => true, 'data' => $ticketDTOs];
    break;

  case 'getTicketById':
    $ticket = $purchasedTicketRepository->getTicketById((int) $request->id);

    if (!$ticket) {
      $response = ['success' => false, 'message' => 'Billet non trouvé'];
    } else {
      $response = [
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
      ];
    }
    break;

  case 'downloadTicket':
    $ticketGenerated = $purchasedTicketRepository->getTicketById((int) $request->id);

    if (!$ticketGenerated) {
      $response = ['success' => false, 'message' => 'Billet non trouvé'];
      echo json_encode($response);
      exit;
    }

    // Vérifier que l'utilisateur possède bien ce billet
    $orderItem = $orderItemRepository->getOrderItemById($ticketGenerated->order_item_id);
    if (!$orderItem) {
      $response = ['success' => false, 'message' => 'Commande introuvable'];
      echo json_encode($response);
      exit;
    }

    $order = $orderRepository->getOrderById($orderItem->order_id);
    if (!$order || $order->user_id !== $userId) {
      $response = ['success' => false, 'message' => 'Accès non autorisé à ce billet'];
      echo json_encode($response);
      exit;
    }

    // Générer le PDF via PdfService
    $result = $pdfService->generateTicketPdf($ticketGenerated, $userId);

    if (!$result['success']) {
      $response = $result;
      echo json_encode($response);
      exit;
    }

    // Envoyer le PDF au client
    $pdfPath = $result['data']['pdf_path'];
    $filename = $result['data']['filename'];

    if (!file_exists($pdfPath)) {
      $response = ['success' => false, 'message' => 'Fichier PDF introuvable'];
      echo json_encode($response);
      exit;
    }

    // Modifier les headers pour l'envoi du fichier
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($pdfPath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    ob_clean();
    flush();
    readfile($pdfPath);
    exit;
    break;

  default:
    $response = ['success' => false, 'message' => 'Action non reconnue'];
    break;
}

echo json_encode($response);
