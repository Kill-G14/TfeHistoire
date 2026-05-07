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

  // ========================================
  // MÉTHODES STRIPE CONNECT
  // ========================================

  /**
   * Mettre à jour les informations Stripe Connect d'un utilisateur
   */
  public function updateStripeAccount(
    int $userId, 
    string $stripeAccountId, 
    string $status, 
    bool $onboardingCompleted
  ): bool {
    $query = "UPDATE users 
              SET stripe_account_id = :stripe_account_id,
                  stripe_account_status = :status,
                  stripe_onboarding_completed = :onboarding_completed,
                  stripe_connected_at = IF(:onboarding_completed = 1, NOW(), stripe_connected_at),
                  updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':stripe_account_id', $stripeAccountId);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':onboarding_completed', $onboardingCompleted, PDO::PARAM_BOOL);
    
    return $stmt->execute();
  }

  /**
   * Récupérer le statut Stripe Connect d'un utilisateur
   */
  public function getStripeAccountStatus(int $userId): ?array {
    $query = "SELECT stripe_account_id, stripe_account_status, 
              stripe_onboarding_completed, stripe_connected_at
              FROM users 
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
  }

  /**
   * Vérifier si un utilisateur a un compte Stripe connecté et actif
   */
  public function hasActiveStripeAccount(int $userId): bool {
    $stripeData = $this->getStripeAccountStatus($userId);
    
    return $stripeData && 
           !empty($stripeData['stripe_account_id']) && 
           $stripeData['stripe_onboarding_completed'] && 
           $stripeData['stripe_account_status'] === 'connected';
  }
}

