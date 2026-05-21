<?php

namespace App\Repositories;

use App\Models\PasswordReset;
use App\Utils\Database;
use PDO;

class PasswordResetRepository
{
    private ?PDO $pdo = null;

    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        return $this->pdo;
    }

    /**
     * Créer une nouvelle demande de réinitialisation
     */
    public function create(int $userId, string $code): bool
    {
        $query = "INSERT INTO password_resets (user_id, code, expires_at) 
                  VALUES (:user_id, :code, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Récupérer une demande par user_id et code (non expirée)
     */
    public function findByUserAndCode(int $userId, string $code): ?PasswordReset
    {
        $query = "SELECT * FROM password_resets 
                  WHERE user_id = :user_id 
                  AND code = :code 
                  AND expires_at > NOW()
                  ORDER BY created_at DESC
                  LIMIT 1";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, PasswordReset::class);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Incrémenter le nombre de tentatives
     */
    public function incrementAttempts(int $id): bool
    {
        $query = "UPDATE password_resets 
                  SET attempts = attempts + 1 
                  WHERE id = :id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Supprimer toutes les demandes d'un utilisateur
     */
    public function deleteByUserId(int $userId): bool
    {
        $query = "DELETE FROM password_resets WHERE user_id = :user_id";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }

    /**
     * Nettoyer les codes expirés (cron/maintenance)
     */
    public function deleteExpired(): int
    {
        $query = "DELETE FROM password_resets WHERE expires_at < NOW()";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}
