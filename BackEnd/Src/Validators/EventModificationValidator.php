<?php

namespace App\Validators;

class EventModificationValidator {
  
  /**
   * Valider une demande de modification de date/heure
   */
  public function validate(string $date, string $time): array {
    $errors = [];

    // Validation de la date
    if (empty($date)) {
      $errors[] = 'La date est obligatoire';
    } else {
      // Vérifier le format de la date (YYYY-MM-DD)
      $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
      if (!preg_match($datePattern, $date)) {
        $errors[] = 'La date doit être au format YYYY-MM-DD';
      } else {
        // Vérifier que la date est valide
        $dateParts = explode('-', $date);
        if (!checkdate((int)$dateParts[1], (int)$dateParts[2], (int)$dateParts[0])) {
          $errors[] = 'La date n\'est pas valide';
        } else {
          // Vérifier que la date est dans le futur
          $eventDate = strtotime($date);
          $now = strtotime(date('Y-m-d'));
          if ($eventDate < $now) {
            $errors[] = 'La date doit être dans le futur';
          }
        }
      }
    }

    // Validation de l'heure
    if (empty($time)) {
      $errors[] = 'L\'heure est obligatoire';
    } else {
      // Vérifier le format de l'heure (HH:MM:SS ou HH:MM)
      $timePattern = '/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/';
      if (!preg_match($timePattern, $time)) {
        $errors[] = 'L\'heure doit être au format HH:MM ou HH:MM:SS';
      }
    }

    return $errors;
  }
}
