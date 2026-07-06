<?php

namespace App\Validators;

use App\Utils\Helpers;

class ContactValidator {
  // Valider les données du formulaire de contact
  public function validate(array $data): array {
    $errors = [];

    // Name
    if (Helpers::isEmpty($data['name'] ?? null)) {
      $errors['name'] = 'Veuillez indiquer votre nom';
    } elseif (strlen($data['name']) < 2) {
      $errors['name'] = 'Le nom est trop court. Veuillez saisir au moins 2 caractères';
    }

    // Email
    if (Helpers::isEmpty($data['email'] ?? null)) {
      $errors['email'] = 'Veuillez indiquer votre adresse email';
    } elseif (!Helpers::isValidEmail($data['email'])) {
      $errors['email'] = 'L\'adresse email n\'est pas valide';
    }

    // Subject
    if (Helpers::isEmpty($data['subject'] ?? null)) {
      $errors['subject'] = 'Veuillez indiquer le sujet de votre demande';
    } elseif (strlen($data['subject']) < 3) {
      $errors['subject'] = 'Le sujet est trop court. Veuillez saisir au moins 3 caractères';
    }

    // Message
    if (Helpers::isEmpty($data['message'] ?? null)) {
      $errors['message'] = 'Veuillez décrire votre demande';
    } elseif (strlen($data['message']) < 10) {
      $errors['message'] = 'Le message est trop court. Veuillez saisir au moins 10 caractères';
    }

    return $errors;
  }
}
