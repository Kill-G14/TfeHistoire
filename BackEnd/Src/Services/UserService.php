<?php

namespace App\Services;

use App\Models\User;
use App\Models\ModelsDTO\UserDTO;
use App\Repositories\UserRepository;
use App\Utils\Logger;

class UserService {
  private UserRepository $userRepository;

  public function __construct(UserRepository $userRepository) {
    $this->userRepository = $userRepository;
  }

  // Récupérer tous les utilisateurs (admin)
  public function getAllUsers(): array {
    $users = $this->userRepository->getAllUsers();
    
    $userDTOs = array_map(function($user) {
      return (new UserDTO($user))->toArray();
    }, $users);

    return [
      'success' => true,
      'message' => 'Utilisateurs récupérés avec succès',
      'data' => $userDTOs
    ];
  }

  // Récupérer un utilisateur par ID (admin)
  public function getUserById(int $id): array {
    $user = $this->userRepository->getUserById($id);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    return [
      'success' => true,
      'message' => 'Utilisateur récupéré avec succès',
      'data' => (new UserDTO($user))->toArray()
    ];
  }

  // Mettre à jour les droits d'un utilisateur (admin)
  public function updateUserRoles(int $id, bool $isAdmin, bool $isOrganizer, bool $isModerator): array {
    $user = $this->userRepository->getUserById($id);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    $success = $this->userRepository->updateUserRoles($id, $isAdmin, $isOrganizer, $isModerator);

    if (!$success) {
      Logger::error('Failed to update user roles', ['user_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des droits'
      ];
    }

    Logger::info('User roles updated successfully', ['user_id' => $id]);

    return [
      'success' => true,
      'message' => 'Droits mis à jour avec succès'
    ];
  }

  // Mettre à jour les informations d'un utilisateur (admin)
  public function updateUserInfo(int $id, string $name, string $email): array {
    $user = $this->userRepository->getUserById($id);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    // Vérifier si l'email est déjà utilisé par un autre utilisateur
    if ($email !== $user->email) {
      $existingUser = $this->userRepository->getUserByEmail($email);
      if ($existingUser && $existingUser->id !== $id) {
        return [
          'success' => false,
          'message' => 'Cet email est déjà utilisé par un autre utilisateur'
        ];
      }
    }

    // Valider le format de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return [
        'success' => false,
        'message' => 'Format d\'email invalide'
      ];
    }

    // Valider le nom (non vide et longueur)
    if (empty(trim($name)) || strlen($name) < 2) {
      return [
        'success' => false,
        'message' => 'Le nom doit contenir au moins 2 caractères'
      ];
    }

    $success = $this->userRepository->updateUserInfo($id, trim($name), trim($email));

    if (!$success) {
      Logger::error('Failed to update user info', ['user_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des informations'
      ];
    }

    Logger::info('User info updated successfully', ['user_id' => $id]);

    return [
      'success' => true,
      'message' => 'Informations mises à jour avec succès'
    ];
  }

  // Supprimer un utilisateur (admin)
  public function adminDeleteUser(int $id): array {
    $user = $this->userRepository->getUserById($id);

    if (!$user) {
      return [
        'success' => false,
        'message' => 'Utilisateur non trouvé'
      ];
    }

    // Empêcher la suppression du dernier admin
    if ($user->is_admin) {
      $allUsers = $this->userRepository->getAllUsers();
      $adminCount = 0;
      foreach ($allUsers as $u) {
        if ($u->is_admin) {
          $adminCount++;
        }
      }
      
      if ($adminCount <= 1) {
        return [
          'success' => false,
          'message' => 'Impossible de supprimer le dernier administrateur'
        ];
      }
    }

    $success = $this->userRepository->deleteUser($id);

    if (!$success) {
      Logger::error('Failed to delete user', ['user_id' => $id]);
      return [
        'success' => false,
        'message' => 'Erreur lors de la suppression de l\'utilisateur'
      ];
    }

    Logger::info('User deleted by admin', ['user_id' => $id]);

    return [
      'success' => true,
      'message' => 'Utilisateur supprimé avec succès'
    ];
  }
}
