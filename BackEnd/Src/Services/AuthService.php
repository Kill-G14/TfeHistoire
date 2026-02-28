<?php

namespace App\Services;

use App\Models\User;
use App\Models\ModelsDTO\UserDTO;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Utils\Logger;

class AuthService {
  private UserRepository $userRepository;

  public function __construct(UserRepository $userRepository) {
    $this->userRepository = $userRepository;
  }

  // Inscription
  public function register(array $data): array {
    // Validation
    $errors = UserValidator::validateRegister($data);
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

    // Générer un token (simple simulation)
    $token = bin2hex(random_bytes(32));

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
    $errors = UserValidator::validateLogin($data);
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

    // Générer un token (simple simulation)
    $token = bin2hex(random_bytes(32));

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

  // Vérifier un token (simple simulation)
  public function verifyToken(string $token): ?int {
    // Dans une vraie application, vérifier le token en base de données
    // Ici, c'est une simulation
    if (strlen($token) === 64) {
      return 1; // Retourne un ID utilisateur fictif
    }
    return null;
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
