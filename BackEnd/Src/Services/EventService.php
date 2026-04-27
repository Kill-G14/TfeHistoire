<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ModelsDTO\EventDTO;
use App\Repositories\EventRepository;
use App\Validators\EventValidator;
use App\Utils\Logger;

class EventService {
  private EventRepository $eventRepository;
  private EventValidator $eventValidator;

  public function __construct(EventRepository $eventRepository, EventValidator $eventValidator) {
    $this->eventRepository = $eventRepository;
    $this->eventValidator = $eventValidator;
  }

  // Récupérer tous les événements
  public function getAllEvents(): array {
    $events = $this->eventRepository->getAllEvents();
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Récupérer tous les événements pour l'admin (tous les statuts)
  public function getAllEventsForAdmin(): array {
    $events = $this->eventRepository->getAllEventsForAdmin();
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Récupérer un événement par ID
  public function getEventById(int $id): array {
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    return [
      'success' => true,
      'data' => (new EventDTO($event))->toArray()
    ];
  }

  // Récupérer les événements par pays
  public function getEventsByCountry(string $country): array {
    $events = $this->eventRepository->getEventsByCountry($country);
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Récupérer les événements par catégorie
  public function getEventsByCategory(string $category): array {
    $events = $this->eventRepository->getEventsByCategory($category);
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Récupérer les événements créés par un utilisateur
  public function getEventsByUserId(int $userId): array {
    $events = $this->eventRepository->getEventsByUserId($userId);
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Créer un événement
  public function createEvent(array $data, int $userId): array {
    // Validation
    $errors = $this->eventValidator->validate($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Créer l'objet Event
    $event = new Event();
    $event->user_id = $userId;
    $event->title = $data['title'];
    $event->description = $data['description'];
    $event->country = $data['country'];
    $event->city = $data['city'];
    $event->postal_code = $data['postal_code'];
    $event->address = $data['address'];
    $event->latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
    $event->longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
    $event->date = $data['date'];
    $event->time = $data['time'];
    $event->category = $data['category'];
    $event->is_free = (bool) $data['is_free'];
    $event->ticket_price = isset($data['ticket_price']) ? (float) $data['ticket_price'] : 0.00;
    $event->ticket_quantity = isset($data['ticket_quantity']) ? (int) $data['ticket_quantity'] : 0;
    $event->image_event = $data['image_event'] ?? null;

    // Insérer en base de données
    $eventId = $this->eventRepository->createEvent($event);

    if (!$eventId) {
      Logger::error('Failed to create event', ['user_id' => $userId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création de l\'événement'
      ];
    }

    // Récupérer l'événement créé
    $createdEvent = $this->eventRepository->getEventById($eventId);

    Logger::info('Event created successfully', ['event_id' => $eventId]);

    return [
      'success' => true,
      'message' => 'Événement créé avec succès',
      'data' => (new EventDTO($createdEvent))->toArray()
    ];
  }

  // Mettre à jour un événement
  public function updateEvent(int $id, array $data, int $userId): array {
    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier que l'utilisateur est le créateur
    if ($event->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à modifier cet événement'
      ];
    }

    // Validation
    $errors = $this->eventValidator->validate($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Mettre à jour les propriétés
    $event->title = $data['title'];
    $event->description = $data['description'];
    $event->country = $data['country'];
    $event->city = $data['city'];
    $event->postal_code = $data['postal_code'];
    $event->address = $data['address'];
    $event->latitude = isset($data['latitude']) ? (float) $data['latitude'] : $event->latitude;
    $event->longitude = isset($data['longitude']) ? (float) $data['longitude'] : $event->longitude;
    $event->date = $data['date'];
    $event->time = $data['time'];
    $event->category = $data['category'];
    $event->is_free = (bool) $data['is_free'];
    $event->ticket_price = isset($data['ticket_price']) ? (float) $data['ticket_price'] : $event->ticket_price;
    $event->ticket_quantity = isset($data['ticket_quantity']) ? (int) $data['ticket_quantity'] : $event->ticket_quantity;
    $event->image_event = $data['image_event'] ?? $event->image_event;

    // Mettre à jour en base de données
    $success = $this->eventRepository->updateEvent($event);

    if (!$success) {
      Logger::error('Failed to update event', ['event_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour de l\'événement'
      ];
    }

    // Récupérer l'événement mis à jour
    $updatedEvent = $this->eventRepository->getEventById($id);

    Logger::info('Event updated successfully', ['event_id' => $id]);

    return [
      'success' => true,
      'message' => 'Événement mis à jour avec succès',
      'data' => (new EventDTO($updatedEvent))->toArray()
    ];
  }

  // Supprimer un événement
  public function deleteEvent(int $id, int $userId): array {
    // Vérifier que l'événement existe
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    // Vérifier que l'utilisateur est le créateur
    if ($event->user_id !== $userId) {
      return [
        'success' => false,
        'message' => 'Vous n\'êtes pas autorisé à supprimer cet événement'
      ];
    }

    // Supprimer l'événement
    $success = $this->eventRepository->deleteEvent($id);

    if (!$success) {
      Logger::error('Failed to delete event', ['event_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la suppression de l\'événement'
      ];
    }

    Logger::info('Event deleted successfully', ['event_id' => $id]);

    return [
      'success' => true,
      'message' => 'Événement supprimé avec succès'
    ];
  }

  // Rechercher des événements
  public function searchEvents(string $search): array {
    $events = $this->eventRepository->searchEvents($search);
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Récupérer les événements en attente (admin)
  public function getPendingEvents(): array {
    $events = $this->eventRepository->getPendingEvents();
    
    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'data' => $eventDTOs
    ];
  }

  // Approuver un événement (admin)
  public function approveEvent(int $id): array {
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    $success = $this->eventRepository->approveEvent($id);

    if (!$success) {
      Logger::error('Failed to approve event', ['event_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de l\'approbation de l\'événement'
      ];
    }

    Logger::info('Event approved successfully', ['event_id' => $id]);

    return [
      'success' => true,
      'message' => 'Événement approuvé avec succès'
    ];
  }

  // Rejeter un événement (admin)
  public function rejectEvent(int $id): array {
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    $success = $this->eventRepository->rejectEvent($id);

    if (!$success) {
      Logger::error('Failed to reject event', ['event_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors du rejet de l\'événement'
      ];
    }

    Logger::info('Event rejected successfully', ['event_id' => $id]);

    return [
      'success' => true,
      'message' => 'Événement rejeté avec succès'
    ];
  }

  // Supprimer un événement (admin - hard delete)
  public function adminDeleteEvent(int $id): array {
    $event = $this->eventRepository->getEventById($id);

    if (!$event) {
      return [
        'success' => false,
        'message' => 'Événement non trouvé'
      ];
    }

    $success = $this->eventRepository->deleteEvent($id);

    if (!$success) {
      Logger::error('Failed to delete event', ['event_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la suppression de l\'événement'
      ];
    }

    Logger::info('Event deleted by admin', ['event_id' => $id]);

    return [
      'success' => true,
      'message' => 'Événement supprimé avec succès'
    ];
  }
}
