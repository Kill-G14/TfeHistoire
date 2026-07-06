<?php

namespace App\Repositories;

use App\Models\ContactMessage;
use App\Utils\Database;
use PDO;

class ContactMessageRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
    $this->ensureTableExists();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // S'assurer que la table contact_messages existe (pas de fichier de migration accessible en local)
  private function ensureTableExists(): void {
    $this->pdo->exec("
      CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_treated BOOLEAN NOT NULL DEFAULT FALSE,
        is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_is_treated (is_treated)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
  }

  // Créer un nouveau message de contact
  public function create(string $name, string $email, string $subject, string $message): ?int {
    $query = "INSERT INTO contact_messages (name, email, subject, message)
              VALUES (:name, :email, :subject, :message)";

    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':subject', $subject);
    $stmt->bindParam(':message', $message);

    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }

    return null;
  }

  // Récupérer tous les messages non supprimés (les plus récents en premier)
  public function getAll(): array {
    $query = "SELECT * FROM contact_messages WHERE is_deleted = FALSE ORDER BY created_at DESC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, ContactMessage::class);
    return $stmt->fetchAll();
  }

  // Récupérer un message par ID
  public function getById(int $id): ?ContactMessage {
    $query = "SELECT * FROM contact_messages WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, ContactMessage::class);
    $contactMessage = $stmt->fetch();
    return $contactMessage ?: null;
  }

  // Marquer un message comme traité
  public function markAsTreated(int $id): bool {
    $query = "UPDATE contact_messages SET is_treated = TRUE WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }
}
