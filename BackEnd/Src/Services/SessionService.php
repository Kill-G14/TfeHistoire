<?php

namespace App\Services;

use App\Repositories\SessionRepository;

class SessionService {
  private SessionRepository $sessionRepository;

  public function __construct(SessionRepository $sessionRepository) {
    $this->sessionRepository = $sessionRepository;
  }

  // Créer une session et retourner le token
  public function createSession(int $userId): ?string {
    // Génération d'un token de session unique de 16 caractères hexadécimaux
    $token = bin2hex(random_bytes(8));

    $created = $this->sessionRepository->createSession($token, $userId);

    return $created ? $token : null;
  }

  // Vérifier si un token est valide
  public function checkToken(string $token): bool {
    return $this->sessionRepository->tokenExists($token);
  }

  // Récupérer l'ID utilisateur depuis un token
  public function getUserIdByToken(string $token): ?int {
    return $this->sessionRepository->getUserIdByToken($token);
  }

  // Supprimer une session par token (déconnexion)
  public function deleteSessionByToken(string $token): bool {
    return $this->sessionRepository->deleteSessionByToken($token);
  }

  // Supprimer toutes les sessions d'un utilisateur
  public function deleteUserSessions(int $userId): bool {
    return $this->sessionRepository->deleteUserSessions($userId);
  }
}
