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
}
