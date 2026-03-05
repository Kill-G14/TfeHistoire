<?php

namespace App\Repositories;

use App\Models\Favorite;
use App\Utils\Database;
use PDO;

class FavoriteRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un favori par ID
  public function getFavoriteById(int $id): ?Favorite {
    $query = "SELECT * FROM favorites WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Favorite::class);
    $favorite = $stmt->fetch();
    return $favorite ?: null;
  }

  // Récupérer les favoris d'un utilisateur
  public function getFavoritesByUserId(int $userId): array {
    $query = "SELECT * FROM favorites WHERE user_id = :user_id AND is_deleted = FALSE ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Favorite::class);
    return $stmt->fetchAll();
  }

  // Vérifier si un événement est en favori
  public function isFavorite(int $userId, int $eventId): bool {
    $query = "SELECT COUNT(*) as count FROM favorites 
              WHERE user_id = :user_id AND event_id = :event_id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    
    return $result['count'] > 0;
  }

  // Ajouter un favori
  public function addFavorite(int $userId, int $eventId): ?int {
    // Vérifier si déjà en favori
    if ($this->isFavorite($userId, $eventId)) {
      return null;
    }

    $query = "INSERT INTO favorites (user_id, event_id, created_at)
              VALUES (:user_id, :event_id, NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Supprimer un favori (soft delete)
  public function deleteFavorite(int $userId, int $eventId): bool {
    $query = "UPDATE favorites SET is_deleted = TRUE 
              WHERE user_id = :user_id AND event_id = :event_id";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    
    return $stmt->execute();
  }

  // Récupérer les événements favoris avec leurs détails
  public function getFavoriteEventsWithDetails(int $userId): array {
    $query = "SELECT e.*, f.id as favorite_id, f.created_at as favorited_at 
              FROM favorites f
              INNER JOIN events e ON f.event_id = e.id
              WHERE f.user_id = :user_id AND f.is_deleted = FALSE AND e.is_deleted = FALSE
              ORDER BY f.created_at DESC";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
