<?php

namespace App\Repositories;

use App\Models\Event;
use App\Utils\Database;
use PDO;

class EventRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un événement par ID
  public function getEventById(int $id): ?Event {
    $query = "SELECT * FROM events WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    $event = $stmt->fetch();
    return $event ?: null;
  }

  // Récupérer tous les événements (approuvés uniquement)
  public function getAllEvents(): array {
    $query = "SELECT * FROM events 
              WHERE is_deleted = FALSE AND is_approved = TRUE 
              ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer tous les événements pour l'admin (tous les statuts)
  public function getAllEventsForAdmin(): array {
    $query = "SELECT * FROM events 
              WHERE is_deleted = FALSE 
              ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements en attente de modération
  public function getPendingEvents(): array {
    $query = "SELECT * FROM events WHERE is_deleted = FALSE AND is_pending = TRUE ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par pays
  public function getEventsByCountry(string $country): array {
    $query = "SELECT * FROM events 
              WHERE country = :country AND is_deleted = FALSE AND is_approved = TRUE 
              ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':country', $country);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par catégorie
  public function getEventsByCategory(string $category): array {
    $query = "SELECT * FROM events 
              WHERE category = :category AND is_deleted = FALSE AND is_approved = TRUE 
              ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':category', $category);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par utilisateur
  public function getEventsByUserId(int $userId): array {
    $query = "SELECT * FROM events 
              WHERE user_id = :user_id AND is_deleted = FALSE 
              ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Créer un nouvel événement
  public function createEvent(Event $event): ?int {
    $query = "INSERT INTO events (user_id, title, description, country, city, postal_code, 
              address, latitude, longitude, date, time, category, is_free, ticket_price, ticket_quantity, image_event, created_at, updated_at)
              VALUES (:user_id, :title, :description, :country, :city, :postal_code, 
              :address, :latitude, :longitude, :date, :time, :category, :is_free, :ticket_price, :ticket_quantity, :image_event, NOW(), NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $event->user_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $event->title);
    $stmt->bindParam(':description', $event->description);
    $stmt->bindParam(':country', $event->country);
    $stmt->bindParam(':city', $event->city);
    $stmt->bindParam(':postal_code', $event->postal_code);
    $stmt->bindParam(':address', $event->address);
    $stmt->bindParam(':latitude', $event->latitude);
    $stmt->bindParam(':longitude', $event->longitude);
    $stmt->bindParam(':date', $event->date);
    $stmt->bindParam(':time', $event->time);
    $stmt->bindParam(':category', $event->category);
    $stmt->bindParam(':is_free', $event->is_free, PDO::PARAM_INT);
    $stmt->bindParam(':ticket_price', $event->ticket_price);
    $stmt->bindParam(':ticket_quantity', $event->ticket_quantity, PDO::PARAM_INT);
    $stmt->bindParam(':image_event', $event->image_event);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Mettre à jour un événement
  public function updateEvent(Event $event): bool {
    $query = "UPDATE events SET 
              title = :title,
              description = :description,
              country = :country,
              city = :city,
              postal_code = :postal_code,
              address = :address,
              latitude = :latitude,
              longitude = :longitude,
              date = :date,
              time = :time,
              category = :category,
              is_free = :is_free,
              ticket_price = :ticket_price,
              ticket_quantity = :ticket_quantity,
              image_event = :image_event,
              updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $event->id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $event->title);
    $stmt->bindParam(':description', $event->description);
    $stmt->bindParam(':country', $event->country);
    $stmt->bindParam(':city', $event->city);
    $stmt->bindParam(':postal_code', $event->postal_code);
    $stmt->bindParam(':address', $event->address);
    $stmt->bindParam(':latitude', $event->latitude);
    $stmt->bindParam(':longitude', $event->longitude);
    $stmt->bindParam(':date', $event->date);
    $stmt->bindParam(':time', $event->time);
    $stmt->bindParam(':category', $event->category);
    $stmt->bindParam(':is_free', $event->is_free, PDO::PARAM_INT);
    $stmt->bindParam(':ticket_price', $event->ticket_price);
    $stmt->bindParam(':ticket_quantity', $event->ticket_quantity, PDO::PARAM_INT);
    $stmt->bindParam(':image_event', $event->image_event);
    
    return $stmt->execute();
  }

  // Supprimer un événement (soft delete)
  public function deleteEvent(int $id): bool {
    $query = "UPDATE events SET is_deleted = TRUE, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Approuver un événement
  public function approveEvent(int $id): bool {
    $query = "UPDATE events SET is_pending = FALSE, is_approved = TRUE, is_rejected = FALSE, updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Rejeter un événement
  public function rejectEvent(int $id): bool {
    $query = "UPDATE events SET is_pending = FALSE, is_approved = FALSE, is_rejected = TRUE, updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Rechercher des événements
  public function searchEvents(string $search): array {
    $searchTerm = '%' . $search . '%';
    $query = "SELECT * FROM events 
              WHERE (title LIKE :search 
              OR description LIKE :search 
              OR country LIKE :search 
              OR city LIKE :search 
              OR category LIKE :search)
              AND is_deleted = FALSE AND is_approved = TRUE
              ORDER BY date ASC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements gratuits
  public function getFreeEvents(): array {
    $query = "SELECT * FROM events WHERE is_free = TRUE AND is_deleted = FALSE AND is_approved = TRUE ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements payants
  public function getPaidEvents(): array {
    $query = "SELECT * FROM events WHERE is_free = FALSE AND is_deleted = FALSE AND is_approved = TRUE ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Mettre à jour la date et l'heure d'un événement
  public function updateEventDateTime(int $eventId, string $newDate, string $newTime): bool {
    $query = "UPDATE events 
              SET date = :new_date, time = :new_time, has_pending_modification = FALSE, updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':new_date', $newDate);
    $stmt->bindParam(':new_time', $newTime);
    return $stmt->execute();
  }

  // Marquer qu'un événement a une modification en attente
  public function setHasPendingModification(int $eventId, bool $hasPending): bool {
    $query = "UPDATE events 
              SET has_pending_modification = :has_pending, updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':has_pending', $hasPending, PDO::PARAM_BOOL);
    return $stmt->execute();
  }

  // Marquer qu'un événement a une demande de suppression
  public function setDeletionRequested(int $eventId, bool $requested, ?string $message = null): bool {
    $query = "UPDATE events 
              SET deletion_requested = :requested, 
                  deletion_message = :message, 
                  deletion_requested_at = NOW(), 
                  updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':requested', $requested, PDO::PARAM_BOOL);
    $stmt->bindParam(':message', $message);
    return $stmt->execute();
  }

  // Récupérer les événements en attente de suppression
  public function getEventsPendingDeletion(): array {
    $query = "SELECT event.*, user.name as user_name, user.email as user_email
              FROM events event
              INNER JOIN users user ON event.user_id = user.id
              WHERE event.deletion_requested = TRUE AND event.is_deleted = FALSE
              ORDER BY event.deletion_requested_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Récupérer les utilisateurs ayant des réservations pour un événement
  public function getUsersWithReservations(int $eventId): array {
    $query = "SELECT DISTINCT user.id, user.email, user.name, reservation.quantity, reservation.created_at as reservation_date
              FROM users user
              INNER JOIN reservations reservation ON user.id = reservation.user_id
              WHERE reservation.event_id = :event_id 
              AND reservation.status = 'confirmed'
              AND reservation.is_deleted = FALSE
              AND user.is_deleted = FALSE
              ORDER BY reservation.created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // Vérifier si un événement a des réservations confirmées
  public function hasReservations(int $eventId): bool {
    $query = "SELECT COUNT(*) as count
              FROM reservations reservation
              WHERE reservation.event_id = :event_id 
              AND reservation.status = 'confirmed'
              AND reservation.is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'] > 0;
  }
}

