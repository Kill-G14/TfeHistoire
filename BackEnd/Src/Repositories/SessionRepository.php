<?php

namespace App\Repositories;

use App\Utils\Database;
use PDO;

class SessionRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Créer une session
  public function createSession(string $token, int $userId): bool {
    $query = "INSERT INTO sessions (token, user_id) VALUES (:token, :userId)";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':userId', $userId);
    return $stmt->execute();
  }

  // Récupérer une session par token
  public function getSessionByToken(string $token): ?array {
    $query = "SELECT * FROM sessions WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  // Vérifier si un token existe
  public function tokenExists(string $token): bool {
    $query = "SELECT COUNT(*) FROM sessions WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
  }

  // Supprimer une session par token
  public function deleteSessionByToken(string $token): bool {
    $query = "DELETE FROM sessions WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    return $stmt->execute();
  }

  // Supprimer toutes les sessions d'un utilisateur
  public function deleteUserSessions(int $userId): bool {
    $query = "DELETE FROM sessions WHERE user_id = :userId";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':userId', $userId);
    return $stmt->execute();
  }

  // Récupérer l'ID utilisateur depuis un token
  public function getUserIdByToken(string $token): ?int {
    $query = "SELECT user_id FROM sessions WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $result = $stmt->fetchColumn();
    return $result ? (int)$result : null;
  }
}
