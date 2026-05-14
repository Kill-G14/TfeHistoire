<?php

namespace App\Validators;

use App\Utils\Helpers;

class UserValidator {
  // Valider les données d'inscription
  public function validateRegister(array $data): array {
    $errors = [];

    // Email
    if (Helpers::isEmpty($data['email'] ?? null)) {
      $errors['email'] = 'Veuillez saisir votre adresse email pour créer votre compte';
    } elseif (!Helpers::isValidEmail($data['email'])) {
      $errors['email'] = 'Cette adresse email n\'est pas valide. Veuillez vérifier le format (exemple : nom@domaine.com)';
    }

    // Password
    if (Helpers::isEmpty($data['password'] ?? null)) {
      $errors['password'] = 'Veuillez choisir un mot de passe pour sécuriser votre compte';
    } elseif (strlen($data['password']) < 6) {
      $errors['password'] = 'Votre mot de passe est trop court. Utilisez au moins 6 caractères pour plus de sécurité';
    }

    // Name
    if (Helpers::isEmpty($data['name'] ?? null)) {
      $errors['name'] = 'Veuillez indiquer votre nom pour personnaliser votre profil';
    } elseif (strlen($data['name']) < 2) {
      $errors['name'] = 'Votre nom est trop court. Veuillez saisir au moins 2 caractères';
    }

    return $errors;
  }

  // Valider les données de connexion
  public function validateLogin(array $data): array {
    $errors = [];

    // Email
    if (Helpers::isEmpty($data['email'] ?? null)) {
      $errors['email'] = 'Veuillez saisir votre adresse email pour vous connecter';
    } elseif (!Helpers::isValidEmail($data['email'])) {
      $errors['email'] = 'L\'adresse email saisie n\'est pas valide. Veuillez vérifier le format';
    }

    // Password
    if (Helpers::isEmpty($data['password'] ?? null)) {
      $errors['password'] = 'Veuillez saisir votre mot de passe pour accéder à votre compte';
    }

    return $errors;
  }

  // Valider les données de mise à jour
  public function validateUpdate(array $data): array {
    $errors = [];

    // Name
    if (isset($data['name']) && !Helpers::isEmpty($data['name'])) {
      if (strlen($data['name']) < 2) {
        $errors['name'] = 'Le nom saisi est trop court. Veuillez entrer au moins 2 caractères';
      }
    }

    return $errors;
  }
}
