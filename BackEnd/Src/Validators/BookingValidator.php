<?php

namespace App\Validators;

class BookingValidator {
  // Valider les données de réservation
  public static function validate(array $data): array {
    $errors = [];

    // Event ID
    if (!isset($data['event_id'])) {
      $errors['event_id'] = 'L\'ID de l\'événement est requis';
    } elseif (!is_numeric($data['event_id']) || $data['event_id'] <= 0) {
      $errors['event_id'] = 'L\'ID de l\'événement n\'est pas valide';
    }

    // Tickets count
    if (!isset($data['tickets_count'])) {
      $errors['tickets_count'] = 'Le nombre de tickets est requis';
    } elseif (!is_numeric($data['tickets_count']) || $data['tickets_count'] <= 0) {
      $errors['tickets_count'] = 'Le nombre de tickets doit être supérieur à 0';
    }

    return $errors;
  }
}
