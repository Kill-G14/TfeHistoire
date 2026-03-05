<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Utils\Database;
use PDO;

class TicketRepository {
  private PDO $pdo;

  public function __construct() {
    $this->pdo = Database::getConnection();
  }

  private function getPdo(): PDO {
    return $this->pdo;
  }

  // Récupérer un billet par ID
  public function getTicketById(int $id): ?Ticket {
    $query = "SELECT * FROM tickets WHERE id = :id AND is_deleted = FALSE";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Ticket::class);
    $ticket = $stmt->fetch();
    return $ticket ?: null;
  }

  // Récupérer tous les billets d'un événement
  public function getTicketsByEventId(int $eventId): array {
    $query = "SELECT * FROM tickets WHERE event_id = :event_id AND is_deleted = FALSE ORDER BY price ASC";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $eventId, PDO::PARAM_INT);
    $stmt->execute();
    $stmt->setFetchMode(PDO::FETCH_CLASS, Ticket::class);
    return $stmt->fetchAll();
  }

  // Créer un billet
  public function createTicket(Ticket $ticket): ?int {
    $query = "INSERT INTO tickets (event_id, name, description, price, quantity, start_sale_date, end_sale_date, created_at, updated_at)
              VALUES (:event_id, :name, :description, :price, :quantity, :start_sale_date, :end_sale_date, NOW(), NOW())";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':event_id', $ticket->event_id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $ticket->name);
    $stmt->bindParam(':description', $ticket->description);
    $stmt->bindParam(':price', $ticket->price);
    $stmt->bindParam(':quantity', $ticket->quantity, PDO::PARAM_INT);
    $stmt->bindParam(':start_sale_date', $ticket->start_sale_date);
    $stmt->bindParam(':end_sale_date', $ticket->end_sale_date);
    
    if ($stmt->execute()) {
      return (int) $this->getPdo()->lastInsertId();
    }
    
    return null;
  }

  // Mettre à jour un billet
  public function updateTicket(Ticket $ticket): bool {
    $query = "UPDATE tickets SET 
              name = :name,
              description = :description,
              price = :price,
              quantity = :quantity,
              start_sale_date = :start_sale_date,
              end_sale_date = :end_sale_date,
              updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $ticket->id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $ticket->name);
    $stmt->bindParam(':description', $ticket->description);
    $stmt->bindParam(':price', $ticket->price);
    $stmt->bindParam(':quantity', $ticket->quantity, PDO::PARAM_INT);
    $stmt->bindParam(':start_sale_date', $ticket->start_sale_date);
    $stmt->bindParam(':end_sale_date', $ticket->end_sale_date);
    
    return $stmt->execute();
  }

  // Supprimer un billet (soft delete)
  public function deleteTicket(int $id): bool {
    $query = "UPDATE tickets SET is_deleted = TRUE, updated_at = NOW() WHERE id = :id";
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
  }

  // Décrémenter la quantité de billets disponibles
  public function decrementQuantity(int $ticketId, int $count): bool {
    $query = "UPDATE tickets SET quantity = quantity - :count, updated_at = NOW()
              WHERE id = :id AND quantity >= :count AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $ticketId, PDO::PARAM_INT);
    $stmt->bindParam(':count', $count, PDO::PARAM_INT);
    
    return $stmt->execute() && $stmt->rowCount() > 0;
  }

  // Incrémenter la quantité de billets disponibles (pour annulation)
  public function incrementQuantity(int $ticketId, int $count): bool {
    $query = "UPDATE tickets SET quantity = quantity + :count, updated_at = NOW()
              WHERE id = :id AND is_deleted = FALSE";
    
    $stmt = $this->getPdo()->prepare($query);
    $stmt->bindParam(':id', $ticketId, PDO::PARAM_INT);
    $stmt->bindParam(':count', $count, PDO::PARAM_INT);
    
    return $stmt->execute();
  }
}
