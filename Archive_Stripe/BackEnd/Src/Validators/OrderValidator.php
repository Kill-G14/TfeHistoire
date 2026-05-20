<?php

namespace App\Validators;

use App\Utils\Helpers;

class OrderValidator {
  // Valider les données de création de commande
  public function validateCreateOrder(array $data): array {
    $errors = [];

    // User ID
    if (!isset($data['user_id'])) {
      $errors['user_id'] = 'L\'ID de l\'utilisateur est requis';
    } elseif (!is_numeric($data['user_id']) || $data['user_id'] <= 0) {
      $errors['user_id'] = 'L\'ID de l\'utilisateur doit être un nombre positif';
    }

    // Items (array des articles de commande)
    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
      $errors['items'] = 'Au moins un article est requis pour créer une commande';
    } else {
      foreach ($data['items'] as $index => $item) {
        // Event ID
        if (!isset($item['event_id'])) {
          $errors["items.$index.event_id"] = 'L\'ID de l\'événement est requis';
        } elseif (!is_numeric($item['event_id']) || $item['event_id'] <= 0) {
          $errors["items.$index.event_id"] = 'L\'ID de l\'événement doit être un nombre positif';
        }

        // Quantity
        if (!isset($item['quantity'])) {
          $errors["items.$index.quantity"] = 'La quantité est requise';
        } elseif (!is_numeric($item['quantity']) || $item['quantity'] < 1) {
          $errors["items.$index.quantity"] = 'La quantité doit être un nombre positif supérieur à zéro';
        }
      }
    }

    return $errors;
  }
}
