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
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
    $user = $stmt->fetch();
    return $user ?: null;
  }

  // Récupérer un utilisateur par email
  public function getUserByEmail(string $email): ?User {
    $query = "SELECT * FROM users WHERE email = :email";
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

  // Supprimer un utilisateur
  public function deleteUser(int $id): bool {
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Vérifier si un email existe déjà
  public function emailExists(string $email): bool {
    $query = "SELECT COUNT(*) as count FROM users WHERE email = :email";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['count'] > 0;
  }

  // Récupérer tous les utilisateurs
  public function getAllUsers(): array {
    $query = "SELECT * FROM users ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);
    return $stmt->fetchAll();
  }
}
