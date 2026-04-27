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

  // Méthode helper pour mapper les résultats avec tickets
  private function mapResultToEvent(array $row): Event {
    $event = new Event();
    $event->id = (int) $row['id'];
    $event->user_id = (int) $row['user_id'];
    $event->title = $row['title'];
    $event->description = $row['description'];
    $event->country = $row['country'];
    $event->city = $row['city'];
    $event->postal_code = $row['postal_code'];
    $event->address = $row['address'];
    $event->latitude = isset($row['latitude']) ? (float) $row['latitude'] : null;
    $event->longitude = isset($row['longitude']) ? (float) $row['longitude'] : null;
    $event->date = $row['date'];
    $event->time = $row['time'];
    $event->category = $row['category'];
    $event->is_free = (bool) $row['is_free'];
    $event->is_pending = (bool) $row['is_pending'];
    $event->is_approved = (bool) $row['is_approved'];
    $event->is_rejected = (bool) $row['is_rejected'];
    $event->is_deleted = (bool) $row['is_deleted'];
    $event->image_event = $row['image_event'] ?? null;
    $event->created_at = $row['created_at'];
    $event->updated_at = $row['updated_at'];
    
    // Données de tickets (peut être null si pas de ticket)
    $event->ticket_id = isset($row['ticket_id']) ? (int) $row['ticket_id'] : null;
    $event->ticket_price = isset($row['ticket_price']) ? (float) $row['ticket_price'] : null;
    $event->ticket_quantity = isset($row['ticket_quantity']) ? (int) $row['ticket_quantity'] : null;
    
    return $event;
  }

  // Récupérer un événement par ID
  public function getEventById(int $id): ?Event {
    $query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
              FROM events e
              LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
              WHERE e.id = :id AND e.is_deleted = FALSE
              LIMIT 1";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $this->mapResultToEvent($row) : null;
  }

  // Récupérer tous les événements (approuvés uniquement)
  public function getAllEvents(): array {
    $query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
              FROM events e
              LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
              WHERE e.is_deleted = FALSE AND e.is_approved = TRUE 
              ORDER BY e.date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapResultToEvent'], $rows);
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
    $query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
              FROM events e
              LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
              WHERE e.country = :country AND e.is_deleted = FALSE AND e.is_approved = TRUE 
              ORDER BY e.date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':country', $country);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapResultToEvent'], $rows);
  }

  // Récupérer les événements par catégorie
  public function getEventsByCategory(string $category): array {
    $query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
              FROM events e
              LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
              WHERE e.category = :category AND e.is_deleted = FALSE AND e.is_approved = TRUE 
              ORDER BY e.date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':category', $category);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapResultToEvent'], $rows);
  }

  // Récupérer les événements par utilisateur
  public function getEventsByUserId(int $userId): array {
    $query = "SELECT e.*, t.id as ticket_id, t.price as ticket_price, t.quantity as ticket_quantity
              FROM events e
              LEFT JOIN tickets t ON e.id = t.event_id AND t.is_deleted = FALSE
              WHERE e.user_id = :user_id AND e.is_deleted = FALSE 
              ORDER BY e.date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map([$this, 'mapResultToEvent'], $rows);
  }

  // Créer un nouvel événement
  public function createEvent(Event $event): ?int {
    $query = "INSERT INTO events (user_id, title, description, country, city, postal_code, 
              address, latitude, longitude, date, time, category, is_free, image_event, created_at, updated_at)
              VALUES (:user_id, :title, :description, :country, :city, :postal_code, 
              :address, :latitude, :longitude, :date, :time, :category, :is_free, :image_event, NOW(), NOW())";
    
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
}

