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

  // Décrémenter la quantité de tickets disponibles
  public function decrementTicketQuantity(int $eventId, int $quantity): bool {
    $query = "UPDATE events SET ticket_quantity = ticket_quantity - :quantity1 
              WHERE id = :id AND is_deleted = FALSE AND ticket_quantity >= :quantity2";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindValue(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindValue(':quantity1', $quantity, PDO::PARAM_INT);
    $stmt->bindValue(':quantity2', $quantity, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Incrémenter la quantité de tickets disponibles (en cas d'annulation)
  public function incrementTicketQuantity(int $eventId, int $quantity): bool {
    $query = "UPDATE events SET ticket_quantity = ticket_quantity + :quantity 
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
    return $stmt->execute();
  }
}

