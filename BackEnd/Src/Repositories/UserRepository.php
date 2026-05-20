<?php

namespace App\Repositories;

use App\Models\User;
use App\Utils\Database;
use PDO;

class UserRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un utilisateur par ID
  public function getUserById(int $id): ?User {
    $query = "SELECT * FROM users WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
    $user = $stmt->fetch();
    return $user ?: null;
  }

  // Récupérer un utilisateur par email
  public function getUserByEmail(string $email): ?User {
    $query = "SELECT * FROM users WHERE email = :email AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
    $user = $stmt->fetch();
    return $user ?: null;
  }

  // Créer un nouvel utilisateur
  public function createUser(string $email, string $password, string $name): ?int {
    $query = "INSERT INTO users (email, password, name, created_at, updated_at)
              VALUES (:email, :password, :name, NOW(), NOW())";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':name', $name);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Mettre à jour un utilisateur
  public function updateUser(int $id, string $name): bool {
    $query = "UPDATE users SET name = :name, updated_at = NOW()
              WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name);
    return $stmt->execute();
  }

  // Supprimer un utilisateur (soft delete)
  public function deleteUser(int $id): bool {
    $query = "UPDATE users SET is_deleted = TRUE, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Vérifier si un email existe déjà
  public function emailExists(string $email): bool {
    $query = "SELECT COUNT(*) as count FROM users WHERE email = :email AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'] > 0;
  }

  // Mettre à jour le mot de passe
  public function updatePassword(int $id, string $hashedPassword): bool {
    $query = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':password', $hashedPassword);
    return $stmt->execute();
  }

  // Récupérer tous les utilisateurs
  public function getAllUsers(): array {
    $query = "SELECT * FROM users WHERE is_deleted = FALSE ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
    return $stmt->fetchAll();
  }

  // Mettre à jour les droits d'un utilisateur (admin)
  public function updateUserRoles(int $id, bool $isAdmin, bool $isOrganizer, bool $isModerator): bool {
    $query = "UPDATE users SET 
              is_admin = :is_admin, 
              is_organizer = :is_organizer, 
              is_moderator = :is_moderator,
              updated_at = NOW() 
              WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':is_admin', $isAdmin, PDO::PARAM_INT);
    $stmt->bindParam(':is_organizer', $isOrganizer, PDO::PARAM_INT);
    $stmt->bindParam(':is_moderator', $isModerator, PDO::PARAM_INT);
    return $stmt->execute();
  }

  /**
   * Récupérer tous les utilisateurs administrateurs
   */
  public function getAllAdmins(): array {
    $query = "SELECT id, email, name FROM users 
              WHERE is_admin = TRUE AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

