<?php

namespace App\Services;

use App\Repositories\SessionRepository;

class SessionService {
  private SessionRepository $sessionRepository;

  public function __construct(SessionRepository $sessionRepository) {
    $this->sessionRepository = $sessionRepository;
  }

  /**
   * Créer une session et retourner le token
   * Token de 64 caractères hexadécimaux (32 bytes = 256 bits de sécurité)
   * 
   * @param int $userId
   * @return string|null Token de session
   */
  public function createSession(int $userId): ?string {
    // Génération d'un token de session sécurisé de 32 bytes (64 caractères hex)
    // 32 bytes = 256 bits de sécurité (recommandé pour éviter les collisions)
    $token = bin2hex(random_bytes(32));

    // Calculer la date d'expiration (14 jours par défaut)
    $config = require __DIR__ . '/../../config.php';
    $lifetimeDays = $config['security']['session_lifetime_days'] ?? 14;
    $expiresAt = time() + ($lifetimeDays * 86400); // 86400 = secondes dans un jour

    $created = $this->sessionRepository->createSession($token, $userId, $expiresAt);

    return $created ? $token : null;
  }

  /**
   * Vérifier si un token est valide ET le renouveler automatiquement
   * Si le token est valide, on prolonge sa durée de vie de 14 jours
   * 
   * @param string $token
   * @return bool
   */
  public function checkToken(string $token): bool {
    $isValid = $this->sessionRepository->tokenExists($token);
    
    // Si le token est valide, le renouveler automatiquement
    if ($isValid) {
      $this->renewSession($token);
    }
    
    return $isValid;
  }

  /**
   * Renouveler une session (prolonger sa durée de vie)
   * Appelé automatiquement à chaque requête avec un token valide
   * 
   * @param string $token
   * @return bool
   */
  public function renewSession(string $token): bool {
    $config = require __DIR__ . '/../../config.php';
    $lifetimeDays = $config['security']['session_lifetime_days'] ?? 14;
    $newExpiresAt = time() + ($lifetimeDays * 86400);
    
    return $this->sessionRepository->updateSessionExpiration($token, $newExpiresAt);
  }

  /**
   * Récupérer l'ID utilisateur depuis un token
   * Vérifie aussi que la session n'est pas expirée
   * 
   * @param string $token
   * @return int|null
   */
  public function getUserIdByToken(string $token): ?int {
    $userId = $this->sessionRepository->getUserIdByToken($token);
    
    // Si on a trouvé un user_id, renouveler la session automatiquement
    if ($userId) {
      $this->renewSession($token);
    }
    
    return $userId;
  }

  /**
   * Supprimer une session par token (déconnexion)
   * 
   * @param string $token
   * @return bool
   */
  public function deleteSessionByToken(string $token): bool {
    return $this->sessionRepository->deleteSessionByToken($token);
  }

  /**
   * Supprimer toutes les sessions d'un utilisateur
   * 
   * @param int $userId
   * @return bool
   */
  public function deleteUserSessions(int $userId): bool {
    return $this->sessionRepository->deleteUserSessions($userId);
  }

  /**
   * Nettoyer les sessions expirées (à appeler via cron quotidien)
   * 
   * @return int Nombre de sessions supprimées
   */
  public function cleanupExpiredSessions(): int {
    return $this->sessionRepository->deleteExpiredSessions();
  }
}
