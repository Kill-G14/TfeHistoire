<?php

namespace App\Validators;

use App\Utils\Helpers;

class UserValidator {
  // Valider les données d'inscription
  public function validateRegister(array $data): array {
    $errors = [];

    // Email
    if (Helpers::isEmpty($data['email'] ?? null)) {
      $errors['email'] = 'L\'email est requis';
    } elseif (!Helpers::isValidEmail($data['email'])) {
      $errors['email'] = 'L\'email n\'est pas valide';
    }

    // Password
    if (Helpers::isEmpty($data['password'] ?? null)) {
      $errors['password'] = 'Le mot de passe est requis';
    } elseif (strlen($data['password']) < 6) {
      $errors['password'] = 'Le mot de passe doit contenir au moins 6 caractères';
    }

    // Name
    if (Helpers::isEmpty($data['name'] ?? null)) {
      $errors['name'] = 'Le nom est requis';
    } elseif (strlen($data['name']) < 2) {
      $errors['name'] = 'Le nom doit contenir au moins 2 caractères';
    }

    return $errors;
  }

  // Valider les données de connexion
  public function validateLogin(array $data): array {
    $errors = [];

    // Email
    if (Helpers::isEmpty($data['email'] ?? null)) {
      $errors['email'] = 'L\'email est requis';
    } elseif (!Helpers::isValidEmail($data['email'])) {
      $errors['email'] = 'L\'email n\'est pas valide';
    }

    // Password
    if (Helpers::isEmpty($data['password'] ?? null)) {
      $errors['password'] = 'Le mot de passe est requis';
    }

    return $errors;
  }

  // Valider les données de mise à jour
  public function validateUpdate(array $data): array {
    $errors = [];

    // Name
    if (isset($data['name']) && !Helpers::isEmpty($data['name'])) {
      if (strlen($data['name']) < 2) {
        $errors['name'] = 'Le nom doit contenir au moins 2 caractères';
      }
    }

    return $errors;
  }
}
