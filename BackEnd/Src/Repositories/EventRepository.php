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
    $query = "SELECT * FROM events WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    $event = $stmt->fetch();
    return $event ?: null;
  }

  // Récupérer tous les événements
  public function getAllEvents(): array {
    $query = "SELECT * FROM events ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par pays
  public function getEventsByCountry(string $country): array {
    $query = "SELECT * FROM events WHERE country = :country ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':country', $country);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par catégorie
  public function getEventsByCategory(string $category): array {
    $query = "SELECT * FROM events WHERE category = :category ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':category', $category);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Récupérer les événements par utilisateur
  public function getEventsByUserId(int $userId): array {
    $query = "SELECT * FROM events WHERE user_id = :user_id ORDER BY date ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Créer un nouvel événement
  public function createEvent(Event $event): ?int {
    $query = "INSERT INTO events (user_id, title, description, country, city, postal_code, 
              address, date, time, price, category, available_tickets, image_url, created_at, updated_at)
              VALUES (:user_id, :title, :description, :country, :city, :postal_code, 
              :address, :date, :time, :price, :category, :available_tickets, :image_url, NOW(), NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $event->user_id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $event->title);
    $stmt->bindParam(':description', $event->description);
    $stmt->bindParam(':country', $event->country);
    $stmt->bindParam(':city', $event->city);
    $stmt->bindParam(':postal_code', $event->postal_code);
    $stmt->bindParam(':address', $event->address);
    $stmt->bindParam(':date', $event->date);
    $stmt->bindParam(':time', $event->time);
    $stmt->bindParam(':price', $event->price);
    $stmt->bindParam(':category', $event->category);
    $stmt->bindParam(':available_tickets', $event->available_tickets, PDO::PARAM_INT);
    $stmt->bindParam(':image_url', $event->image_url);
    
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
              date = :date,
              time = :time,
              price = :price,
              category = :category,
              available_tickets = :available_tickets,
              image_url = :image_url,
              updated_at = NOW()
              WHERE id = :id";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $event->id, PDO::PARAM_INT);
    $stmt->bindParam(':title', $event->title);
    $stmt->bindParam(':description', $event->description);
    $stmt->bindParam(':country', $event->country);
    $stmt->bindParam(':city', $event->city);
    $stmt->bindParam(':postal_code', $event->postal_code);
    $stmt->bindParam(':address', $event->address);
    $stmt->bindParam(':date', $event->date);
    $stmt->bindParam(':time', $event->time);
    $stmt->bindParam(':price', $event->price);
    $stmt->bindParam(':category', $event->category);
    $stmt->bindParam(':available_tickets', $event->available_tickets, PDO::PARAM_INT);
    $stmt->bindParam(':image_url', $event->image_url);
    
    return $stmt->execute();
  }

  // Supprimer un événement
  public function deleteEvent(int $id): bool {
    $query = "DELETE FROM events WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Rechercher des événements
  public function searchEvents(string $search): array {
    $searchTerm = '%' . $search . '%';
    $query = "SELECT * FROM events 
              WHERE title LIKE :search 
              OR description LIKE :search 
              OR country LIKE :search 
              OR city LIKE :search 
              OR category LIKE :search
              ORDER BY date ASC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Event::class);
    return $stmt->fetchAll();
  }

  // Décrémenter le nombre de tickets disponibles
  public function decrementAvailableTickets(int $eventId, int $count): bool {
    $query = "UPDATE events SET available_tickets = available_tickets - :count, updated_at = NOW()
              WHERE id = :id AND available_tickets >= :count";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $eventId, PDO::PARAM_INT);
    $stmt->bindParam(':count', $count, PDO::PARAM_INT);
    
    return $stmt->execute() && $stmt->rowCount() > 0;
  }
}
