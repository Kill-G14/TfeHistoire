<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\ModelsDTO\TicketDTO;
use App\Repositories\TicketRepository;
use App\Repositories\EventRepository;
use App\Validators\TicketValidator;
use App\Utils\Logger;

class TicketService {
  private TicketRepository $ticketRepository;
  private EventRepository $eventRepository;
  private TicketValidator $ticketValidator;

  public function __construct(TicketRepository $ticketRepository, EventRepository $eventRepository, TicketValidator $ticketValidator) {
    $this->ticketRepository = $ticketRepository;
    $this->eventRepository = $eventRepository;
    $this->ticketValidator = $ticketValidator;
  }

  // Récupérer les billets d'un événement
  public function getTicketsByEventId(int $eventId): array {
    $tickets = $this->ticketRepository->getTicketsByEventId($eventId);
    
    $ticketDTOs = array_map(function($ticket) {
      return (new TicketDTO($ticket))->toArray();
    }, $tickets);

    return [
      'success' => true,
      'data' => $ticketDTOs
    ];
  }

  // Récupérer un billet par ID
  public function getTicketById(int $id): array {
    $ticket = $this->ticketRepository->getTicketById($id);

    if (!$ticket) {
      return [
        'success' => false,
        'message' => 'Billet non trouvé'
      ];
    }

    return [
      'success' => true,
      'data' => (new TicketDTO($ticket))->toArray()
    ];
  }

  // Créer un billet
  public function createTicket(array $data, int $userId): array {
    // Validation
    $errors = $this->ticketValidator->validate($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($data['event_id']);
    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier que l'utilisateur est le créateur de l'événement
    if ($event->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à créer des billets pour cet événement'
      ];
    }

    // Créer l'objet Ticket
    $ticket = new Ticket();
    $ticket->event_id = (int) $data['event_id'];
    $ticket->name = $data['name'];
    $ticket->description = $data['description'] ?? null;
    $ticket->price = (float) $data['price'];
    $ticket->quantity = (int) $data['quantity'];
    $ticket->start_sale_date = $data['start_sale_date'] ?? null;
    $ticket->end_sale_date = $data['end_sale_date'] ?? null;

    // Insérer en base de données
    $ticketId = $this->ticketRepository->createTicket($ticket);

    if (!$ticketId) {
      Logger::error('Failed to create ticket', ['event_id' => $data['event_id']]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création du billet'
      ];
    }

    // Récupérer le billet créé
    $createdTicket = $this->ticketRepository->getTicketById($ticketId);

    Logger::info('Ticket created successfully', ['ticket_id' => $ticketId]);

    return [
      'success' => true,
      'message' => 'Billet créé avec succès',
      'data' => (new TicketDTO($createdTicket))->toArray()
    ];
  }

  // Mettre à jour un billet
  public function updateTicket(int $id, array $data, int $userId): array {
    // Vérifier que le billet existe
    $ticket = $this->ticketRepository->getTicketById($id);

    if (!$ticket) {
      return [
        'success' => false,
        'message' => 'Billet non trouvé'
      ];
    }

    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($ticket->event_id);
    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier que l'utilisateur est le créateur de l'événement
    if ($event->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à modifier ce billet'
      ];
    }

    // Validation
    $errors = $this->ticketValidator->validate($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Mettre à jour les propriétés
    $ticket->name = $data['name'];
    $ticket->description = $data['description'] ?? $ticket->description;
    $ticket->price = (float) $data['price'];
    $ticket->quantity = (int) $data['quantity'];
    $ticket->start_sale_date = $data['start_sale_date'] ?? $ticket->start_sale_date;
    $ticket->end_sale_date = $data['end_sale_date'] ?? $ticket->end_sale_date;

    // Mettre à jour en base de données
    $success = $this->ticketRepository->updateTicket($ticket);

    if (!$success) {
      Logger::error('Failed to update ticket', ['ticket_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du billet'
      ];
    }

    // Récupérer le billet mis à jour
    $updatedTicket = $this->ticketRepository->getTicketById($id);

    Logger::info('Ticket updated successfully', ['ticket_id' => $id]);

    return [
      'success' => true,
      'message' => 'Billet mis à jour avec succès',
      'data' => (new TicketDTO($updatedTicket))->toArray()
    ];
  }

  // Supprimer un billet
  public function deleteTicket(int $id, int $userId): array {
    // Vérifier que le billet existe
    $ticket = $this->ticketRepository->getTicketById($id);

    if (!$ticket) {
      return [
        'success' => false,
        'message' => 'Billet non trouvé'
      ];
    }

    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($ticket->event_id);
    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier que l'utilisateur est le créateur de l'événement
    if ($event->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à supprimer ce billet'
      ];
    }

    // Supprimer le billet
    $success = $this->ticketRepository->deleteTicket($id);

    if (!$success) {
      Logger::error('Failed to delete ticket', ['ticket_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la suppression du billet'
      ];
    }

    Logger::info('Ticket deleted successfully', ['ticket_id' => $id]);

    return [
      'success' => true,
      'message' => 'Billet supprimé avec succès'
    ];
  }
}
