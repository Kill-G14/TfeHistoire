<?php

namespace App\Services;

use App\Models\Event;
use App\Models\ModelsDTO\EventDTO;
use App\Repositories\EventRepository;
use App\Repositories\UserRepository;
use App\Validators\EventValidator;
use App\Utils\Logger;

class EventService {
  private EventRepository $eventRepository;
  private EventValidator $eventValidator;
  private ?UserRepository $userRepository;

  public function __construct(EventRepository $eventRepository, EventValidator $eventValidator, ?UserRepository $userRepository = null) {
    $this->eventRepository = $eventRepository;
    $this->eventValidator = $eventValidator;
    $this->userRepository = $userRepository;
  }

  // Récupérer tous les événements
  public function getAllEvents(): array {
    $events = $this->eventRepository->getAllEvents();

    $eventDTOs = array_map(function($event) {
      return (new EventDTO($event))->toArray();
    }, $events);

    return [
      'success' => true,
      'message' => 'Événements récupérés avec succès',
      'data' => $eventDTOs
    ];
  }

  // Empreinte de la liste publique : le front la compare avant de recharger les événements
  public function getEventsFingerprint(): array {
    return [
      'success' => true,
      'data' => ['fingerprint' => $this->eventRepository->getPublicEventsFingerprint()]
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
      'message' => 'Événements récupérés avec succès',
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
      'message' => 'Événement récupéré avec succès',
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
      'message' => 'Événements récupérés avec succès',
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
      'message' => 'Événements récupérés avec succès',
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
      'message' => 'Événements récupérés avec succès',
      'data' => $eventDTOs
    ];
  }

  // Un prix > 0 exige un organisateur dont le compte Stripe est connecté et prêt à encaisser.
  // Retourne un message d'erreur, ou null si tout est correct.
  private function checkOrganizerCanSellAt(float $price, int $userId): ?string {
    if ($price <= 0) {
      return null;
    }

    $organizer = $this->userRepository ? $this->userRepository->getUserById($userId) : null;
    if (!$organizer || !$organizer->stripe_onboarding_completed || empty($organizer->stripe_account_id)) {
      return 'Connectez votre compte Stripe depuis votre profil pour proposer un événement payant';
    }

    return null;
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

    // Un prix n'est accepté que si l'organisateur peut réellement encaisser (compte Stripe connecté)
    $price = isset($data['price']) && $data['price'] !== '' ? round((float) $data['price'], 2) : 0.0;
    $priceError = $this->checkOrganizerCanSellAt($price, $userId);
    if ($priceError) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => ['price' => $priceError]
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
    // ticket_quantity = capacité maximale de l'événement (0 = illimité)
    $event->ticket_quantity = isset($data['ticket_quantity']) && $data['ticket_quantity'] !== ''
      ? (int) $data['ticket_quantity']
      : 0;
    $event->price = $price;
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

    // Le prix ne change plus dès qu'une réservation existe : les acheteurs ont vu un montant précis
    $price = isset($data['price']) && $data['price'] !== '' ? round((float) $data['price'], 2) : (float) $event->price;
    if ($price !== (float) $event->price) {
      if ($this->eventRepository->hasActiveReservations($id)) {
        return [
          'success' => false,
          'message' => 'Erreur de validation',
          'errors' => ['price' => 'Le prix ne peut plus être modifié : des réservations existent déjà']
        ];
      }

      $priceError = $this->checkOrganizerCanSellAt($price, $userId);
      if ($priceError) {
        return [
          'success' => false,
          'message' => 'Erreur de validation',
          'errors' => ['price' => $priceError]
        ];
      }
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
    // ticket_quantity = capacité maximale de l'événement (0 = illimité)
    $event->ticket_quantity = isset($data['ticket_quantity']) && $data['ticket_quantity'] !== ''
      ? (int) $data['ticket_quantity']
      : 0;
    $event->price = $price;
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
      'message' => 'Résultats de recherche',
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
      'message' => 'Événements en attente récupérés avec succès',
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
