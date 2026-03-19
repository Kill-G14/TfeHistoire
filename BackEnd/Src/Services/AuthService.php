<?php

namespace App\Services;

use App\Models\User;
use App\Models\ModelsDTO\UserDTO;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Services\SessionService;
use App\Utils\Logger;

class AuthService {
  private UserRepository $userRepository;
  private UserValidator $userValidator;
  private SessionService $sessionService;

  public function __construct(UserRepository $userRepository, UserValidator $userValidator, SessionService $sessionService) {
    $this->userRepository = $userRepository;
    $this->userValidator = $userValidator;
    $this->sessionService = $sessionService;
  }

  // Inscription
  public function register(array $data): array {
    // Validation
    $errors = $this->userValidator->validateRegister($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Vérifier si l'email existe déjà
    if ($this->userRepository->emailExists($data['email'])) {
      return [
        'success' => false,
        'message' => 'Cet email est déjà utilisé'
      ];
    }

    // Hasher le mot de passe
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Créer l'utilisateur
    $userId = $this->userRepository->createUser(
      $data['email'],
      $hashedPassword,
      $data['name']
    );

    if (!$userId) {
      Logger::error('Failed to create user', ['email' => $data['email']]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création du compte'
      ];
    }

    // Récupérer l'utilisateur créé
    $user = $this->userRepository->getUserById($userId);
    if (!$user) {
      return [
        'success' => false,
        'message' => 'Erreur lors de la récupération de l\'utilisateur'
      ];
    }

    // Créer une session et générer un token
    $token = $this->sessionService->createSession($userId);

    if (!$token) {
      Logger::error('Failed to create session', ['user_id' => $userId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création de la session'
      ];
    }

    Logger::info('User registered successfully', ['user_id' => $userId]);

    return [
      'success' => true,
      'message' => 'Inscription réussie',
      'data' => [
        'user' => (new UserDTO($user))->toArray(),
        'token' => $token
      ]
    ];
  }

  // Connexion
  public function login(array $data): array {
    // Validation
    $errors = $this->userValidator->validateLogin($data);
    if (!empty($errors)) {
      return [
        'success' => false,
        'message' => 'Erreur de validation',
        'errors' => $errors
      ];
    }

    // Récupérer l'utilisateur par email
    $user = $this->userRepository->getUserByEmail($data['email']);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Email ou mot de passe incorrect'
      ];
    }

    // Vérifier le mot de passe
    if (!password_verify($data['password'], $user->password)) {
      return [
        'success' => false,
        'message' => 'Email ou mot de passe incorrect'
      ];
    }

    // Créer une session et générer un token
    $token = $this->sessionService->createSession($user->id);

    if (!$token) {
      Logger::error('Failed to create session', ['user_id' => $user->id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la création de la session'
      ];
    }

    Logger::info('User logged in successfully', ['user_id' => $user->id]);

    return [
      'success' => true,
      'message' => 'Connexion réussie',
      'data' => [
        'user' => (new UserDTO($user))->toArray(),
        'token' => $token
      ]
    ];
  }

  // Vérifier un token et récupérer l'ID utilisateur
  public function checkToken(string $token): ?int {
    return $this->sessionService->getUserIdByToken($token);
  }

  // Déconnexion (suppression de la session)
  public function logout(string $token): array {
    $deleted = $this->sessionService->deleteSessionByToken($token);

    if (!$deleted) {
      return [
        'success' => false,
        'message' => 'Erreur lors de la déconnexion'
      ];
    }

    Logger::info('User logged out successfully');

    return [
      'success' => true,
      'message' => 'Déconnexion réussie'
    ];
  }

  // Récupérer l'utilisateur connecté
  public function getCurrentUser(int $userId): array {
    $user = $this->userRepository->getUserById($userId);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    return [
      'success' => true,
      'data' => (new UserDTO($user))->toArray()
    ];
  }

  // Changer le mot de passe
  public function changePassword(int $userId, array $data): array {
    // Validation des données
    if (!isset($data['currentPassword']) || !isset($data['newPassword'])) {
      return [
        'success' => false,
        'message' => 'Données manquantes'
      ];
    }

    if (strlen($data['newPassword']) < 6) {
      return [
        'success' => false,
        'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères'
      ];
    }

    // Récupérer l'utilisateur
    $user = $this->userRepository->getUserById($userId);
    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    // Vérifier le mot de passe actuel
    if (!password_verify($data['currentPassword'], $user->password)) {
      return [
        'success' => false,
        'message' => 'Mot de passe actuel incorrect'
      ];
    }

    // Hasher le nouveau mot de passe
    $hashedPassword = password_hash($data['newPassword'], PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $updated = $this->userRepository->updatePassword($userId, $hashedPassword);

    if (!$updated) {
      Logger::error('Failed to update password', ['user_id' => $userId]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour du mot de passe'
      ];
    }

    Logger::info('Password changed successfully', ['user_id' => $userId]);

    return [
      'success' => true,
      'message' => 'Mot de passe modifié avec succès'
    ];
  }

  // Demande de réinitialisation de mot de passe
  public function requestPasswordReset(array $data): array {
    if (!isset($data['email'])) {
      return [
        'success' => false,
        'message' => 'Email manquant'
      ];
    }

    // Vérifier si l'utilisateur existe
    $user = $this->userRepository->getUserByEmail($data['email']);
    
    // Pour des raisons de sécurité, on retourne toujours un succès
    // même si l'email n'existe pas (pour ne pas révéler les emails existants)
    if (!$user) {
      Logger::info('Password reset requested for non-existent email', ['email' => $data['email']]);
      return [
        'success' => true,
        'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
      ];
    }

    // TODO: Ici, dans une vraie application, on devrait :
    // 1. Générer un token de réinitialisation unique
    // 2. Stocker ce token dans la base de données avec une date d'expiration
    // 3. Envoyer un email avec un lien contenant ce token
    // Pour l'instant, on simule juste l'envoi

    Logger::info('Password reset requested', ['user_id' => $user->id]);

    return [
      'success' => true,
      'message' => 'Si cet email existe, un lien de réinitialisation a été envoyé'
    ];
  }
}
