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

  /**
   * Créer une session avec expiration
   * 
   * @param string $token Token de 64 caractères
   * @param int $userId
   * @param int $expiresAt Timestamp d'expiration
   * @return bool
   */
  public function createSession(string $token, int $userId, int $expiresAt): bool {
    $query = "INSERT INTO sessions (token, user_id, expires_at, created_at) 
              VALUES (:token, :userId, FROM_UNIXTIME(:expiresAt), NOW())";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':expiresAt', $expiresAt, PDO::PARAM_INT);
    return $stmt->execute();
  }

  /**
   * Récupérer une session par token (non expirée)
   * 
   * @param string $token
   * @return array|null
   */
  public function getSessionByToken(string $token): ?array {
    $query = "SELECT * FROM sessions 
              WHERE token = :token 
              AND (expires_at IS NULL OR expires_at > NOW())";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  /**
   * Vérifier si un token existe ET n'est pas expiré
   * 
   * @param string $token
   * @return bool
   */
  public function tokenExists(string $token): bool {
    $query = "SELECT COUNT(*) FROM sessions 
              WHERE token = :token 
              AND (expires_at IS NULL OR expires_at > NOW())";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
  }

  /**
   * Mettre à jour la date d'expiration d'une session (renouvellement)
   * 
   * @param string $token
   * @param int $newExpiresAt Nouveau timestamp d'expiration
   * @return bool
   */
  public function updateSessionExpiration(string $token, int $newExpiresAt): bool {
    $query = "UPDATE sessions 
              SET expires_at = FROM_UNIXTIME(:expiresAt), 
                  last_activity = NOW()
              WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':expiresAt', $newExpiresAt, PDO::PARAM_INT);
    return $stmt->execute();
  }

  /**
   * Supprimer une session par token
   * 
   * @param string $token
   * @return bool
   */
  public function deleteSessionByToken(string $token): bool {
    $query = "DELETE FROM sessions WHERE token = :token";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    return $stmt->execute();
  }

  /**
   * Supprimer toutes les sessions d'un utilisateur
   * 
   * @param int $userId
   * @return bool
   */
  public function deleteUserSessions(int $userId): bool {
    $query = "DELETE FROM sessions WHERE user_id = :userId";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    return $stmt->execute();
  }

  /**
   * Récupérer l'ID utilisateur depuis un token (non expiré)
   * 
   * @param string $token
   * @return int|null
   */
  public function getUserIdByToken(string $token): ?int {
    $query = "SELECT user_id FROM sessions 
              WHERE token = :token 
              AND (expires_at IS NULL OR expires_at > NOW())";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $result = $stmt->fetchColumn();
    return $result ? (int)$result : null;
  }

  /**
   * Supprimer les sessions expirées (nettoyage)
   * À appeler via un cron quotidien
   * 
   * @return int Nombre de sessions supprimées
   */
  public function deleteExpiredSessions(): int {
    $query = "DELETE FROM sessions WHERE expires_at < NOW()";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    return $stmt->rowCount();
  }
}
