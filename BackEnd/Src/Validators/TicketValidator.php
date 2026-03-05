<?php

namespace App\Validators;

use App\Utils\Helpers;

class TicketValidator {
  // Valider les données de création de ticket
  public function validate(array $data): array {
    $errors = [];

    // Event ID
    if (!isset($data['event_id'])) {
      $errors['event_id'] = 'L\'ID de l\'événement est requis';
    } elseif (!is_numeric($data['event_id']) || $data['event_id'] <= 0) {
      $errors['event_id'] = 'L\'ID de l\'événement doit être un nombre positif';
    }

    // Name
    if (Helpers::isEmpty($data['name'] ?? null)) {
      $errors['name'] = 'Le nom du billet est requis';
    } elseif (strlen($data['name']) < 3) {
      $errors['name'] = 'Le nom du billet doit contenir au moins 3 caractères';
    }

    // Price
    if (!isset($data['price'])) {
      $errors['price'] = 'Le prix est requis';
    } elseif (!is_numeric($data['price']) || $data['price'] < 0) {
      $errors['price'] = 'Le prix doit être un nombre positif ou zéro';
    }

    // Quantity
    if (!isset($data['quantity'])) {
      $errors['quantity'] = 'La quantité est requise';
    } elseif (!is_numeric($data['quantity']) || $data['quantity'] < 1) {
      $errors['quantity'] = 'La quantité doit être un nombre positif supérieur à zéro';
    }

    // Start sale date (optionnel)
    if (isset($data['start_sale_date']) && !Helpers::isEmpty($data['start_sale_date'])) {
      if (!Helpers::isValidDate($data['start_sale_date'])) {
        $errors['start_sale_date'] = 'La date de début de vente n\'est pas valide';
      }
    }

    // End sale date (optionnel)
    if (isset($data['end_sale_date']) && !Helpers::isEmpty($data['end_sale_date'])) {
      if (!Helpers::isValidDate($data['end_sale_date'])) {
        $errors['end_sale_date'] = 'La date de fin de vente n\'est pas valide';
      }
    }

    return $errors;
  }
}
