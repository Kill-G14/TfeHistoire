<?php

namespace App\Validators;

use App\Utils\Helpers;

class EventValidator {
  // Valider les données de création d'événement
  public function validate(array $data): array {
    $errors = [];

    // Title
    if (Helpers::isEmpty($data['title'] ?? null)) {
      $errors['title'] = 'Veuillez donner un titre à votre événement pour attirer les visiteurs';
    } elseif (strlen($data['title']) < 3) {
      $errors['title'] = 'Le titre est trop court. Veuillez saisir au moins 3 caractères pour décrire votre événement';
    }

    // Description
    if (Helpers::isEmpty($data['description'] ?? null)) {
      $errors['description'] = 'Veuillez décrire votre événement pour donner envie aux participants de venir';
    } elseif (strlen($data['description']) < 10) {
      $errors['description'] = 'La description est trop courte. Décrivez votre événement avec au moins 10 caractères';
    }

    // Country
    if (Helpers::isEmpty($data['country'] ?? null)) {
      $errors['country'] = 'Veuillez sélectionner le pays où se déroule votre événement';
    }

    // City
    if (Helpers::isEmpty($data['city'] ?? null)) {
      $errors['city'] = 'Veuillez indiquer la ville où aura lieu votre événement';
    }

    // Postal code
    if (Helpers::isEmpty($data['postal_code'] ?? null)) {
      $errors['postal_code'] = 'Le code postal est nécessaire pour localiser précisément votre événement';
    }

    // Address
    if (Helpers::isEmpty($data['address'] ?? null)) {
      $errors['address'] = 'Veuillez saisir l\'adresse complète du lieu de l\'événement';
    }

    // Date
    if (Helpers::isEmpty($data['date'] ?? null)) {
      $errors['date'] = 'Veuillez indiquer la date à laquelle aura lieu votre événement';
    } elseif (!Helpers::isValidDate($data['date'])) {
      $errors['date'] = 'Le format de la date n\'est pas valide. Utilisez le format AAAA-MM-JJ (exemple : 2026-12-25)';
    }

    // Time
    if (Helpers::isEmpty($data['time'] ?? null)) {
      $errors['time'] = 'Veuillez préciser l\'heure de début de votre événement';
    } elseif (!Helpers::isValidTime($data['time'])) {
      $errors['time'] = 'Le format de l\'heure n\'est pas valide. Utilisez le format HH:MM (exemple : 14:30)';
    }

    // Category
    if (Helpers::isEmpty($data['category'] ?? null)) {
      $errors['category'] = 'Veuillez choisir une catégorie pour aider les visiteurs à trouver votre événement';
    }

    // Is Free - TOUS LES ÉVÉNEMENTS SONT GRATUITS (système de réservations)
    // On force toujours is_free = TRUE, les champs ticket_price et ticket_quantity sont ignorés
    // Pas de validation car la valeur est forcée côté backend

    // Latitude (optionnel)
    if (isset($data['latitude']) && !Helpers::isEmpty($data['latitude'])) {
      if (!is_numeric($data['latitude']) || $data['latitude'] < -90 || $data['latitude'] > 90) {
        $errors['latitude'] = 'La latitude doit être un nombre compris entre -90 et 90 degrés';
      }
    }

    // Longitude (optionnel)
    if (isset($data['longitude']) && !Helpers::isEmpty($data['longitude'])) {
      if (!is_numeric($data['longitude']) || $data['longitude'] < -180 || $data['longitude'] > 180) {
        $errors['longitude'] = 'La longitude doit être un nombre compris entre -180 et 180 degrés';
      }
    }

    // Image Event (optionnel)
    if (isset($data['image_event']) && !Helpers::isEmpty($data['image_event'])) {
      // Vérifier que c'est un nom de fichier valide (avec extension d'image)
      $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      $extension = strtolower(pathinfo($data['image_event'], PATHINFO_EXTENSION));
      
      if (!in_array($extension, $allowedExtensions)) {
        $errors['image_event'] = 'Le format de l\'image n\'est pas supporté. Utilisez JPG, JPEG, PNG, GIF ou WEBP uniquement';
      }
    }

    return $errors;
  }
}
