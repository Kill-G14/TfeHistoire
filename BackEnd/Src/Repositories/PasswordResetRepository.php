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
    public function create(int $userId, string $code, string $expiresAt): bool
    {
        $query = "INSERT INTO password_resets (user_id, code, expires_at) 
                  VALUES (:user_id, :code, :expires_at)";
        
        $stmt = $this->getPdo()->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':code', $code, PDO::PARAM_STR);
        $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
        
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
        
        // DEBUG: Log pour voir ce qui se passe
        \App\Utils\Logger::info("Looking for password reset", [
            'user_id' => $userId,
            'code_received' => $code,
            'code_length' => strlen($code),
            'found' => $stmt->rowCount() > 0
        ]);
        
        // Si rien trouvé, chercher tous les codes de cet utilisateur pour debug
        if ($stmt->rowCount() === 0) {
            $debugQuery = "SELECT code, expires_at, created_at, CURRENT_TIMESTAMP as now FROM password_resets WHERE user_id = :user_id";
            $debugStmt = $this->getPdo()->prepare($debugQuery);
            $debugStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $debugStmt->execute();
            $allCodes = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            \App\Utils\Logger::info("All codes for user", ['codes' => $allCodes]);
        }
        
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
