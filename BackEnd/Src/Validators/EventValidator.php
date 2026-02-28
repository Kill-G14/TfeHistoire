<?php

namespace App\Validators;

use App\Utils\Helpers;

class EventValidator {
  // Valider les données de création d'événement
  public function validate(array $data): array {
    $errors = [];

    // Title
    if (Helpers::isEmpty($data['title'] ?? null)) {
      $errors['title'] = 'Le titre est requis';
    } elseif (strlen($data['title']) < 3) {
      $errors['title'] = 'Le titre doit contenir au moins 3 caractères';
    }

    // Description
    if (Helpers::isEmpty($data['description'] ?? null)) {
      $errors['description'] = 'La description est requise';
    } elseif (strlen($data['description']) < 10) {
      $errors['description'] = 'La description doit contenir au moins 10 caractères';
    }

    // Country
    if (Helpers::isEmpty($data['country'] ?? null)) {
      $errors['country'] = 'Le pays est requis';
    }

    // City
    if (Helpers::isEmpty($data['city'] ?? null)) {
      $errors['city'] = 'La ville est requise';
    }

    // Postal code
    if (Helpers::isEmpty($data['postal_code'] ?? null)) {
      $errors['postal_code'] = 'Le code postal est requis';
    }

    // Address
    if (Helpers::isEmpty($data['address'] ?? null)) {
      $errors['address'] = 'L\'adresse est requise';
    }

    // Date
    if (Helpers::isEmpty($data['date'] ?? null)) {
      $errors['date'] = 'La date est requise';
    } elseif (!Helpers::isValidDate($data['date'])) {
      $errors['date'] = 'La date n\'est pas valide (format attendu: YYYY-MM-DD)';
    }

    // Time
    if (Helpers::isEmpty($data['time'] ?? null)) {
      $errors['time'] = 'L\'heure est requise';
    } elseif (!Helpers::isValidTime($data['time'])) {
      $errors['time'] = 'L\'heure n\'est pas valide (format attendu: HH:MM)';
    }

    // Price
    if (!isset($data['price'])) {
      $errors['price'] = 'Le prix est requis';
    } elseif (!is_numeric($data['price']) || $data['price'] < 0) {
      $errors['price'] = 'Le prix doit être un nombre positif';
    }

    // Category
    if (Helpers::isEmpty($data['category'] ?? null)) {
      $errors['category'] = 'La catégorie est requise';
    }

    // Available tickets
    if (!isset($data['available_tickets'])) {
      $errors['available_tickets'] = 'Le nombre de tickets est requis';
    } elseif (!is_numeric($data['available_tickets']) || $data['available_tickets'] < 0) {
      $errors['available_tickets'] = 'Le nombre de tickets doit être un nombre positif';
    }

    // Image URL (optionnel)
    if (isset($data['image_url']) && !Helpers::isEmpty($data['image_url'])) {
      if (!Helpers::isValidUrl($data['image_url'])) {
        $errors['image_url'] = 'L\'URL de l\'image n\'est pas valide';
      }
    }

    return $errors;
  }
}
