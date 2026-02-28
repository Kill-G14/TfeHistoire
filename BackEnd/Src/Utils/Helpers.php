<?php

namespace App\Utils;

class Helpers {
  // Valider une adresse email
  public static function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
  }

  // Valider une URL
  public static function isValidUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
  }

  // Nettoyer une chaîne de caractères
  public static function sanitizeString(string $string): string {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
  }

  // Valider une date au format Y-m-d
  public static function isValidDate(string $date): bool {
    $d = \DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
  }

  // Valider une heure au format H:i
  public static function isValidTime(string $time): bool {
    $t = \DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
  }

  // Générer un token aléatoire
  public static function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
  }

  // Formater un prix
  public static function formatPrice(float $price): string {
    return number_format($price, 2, '.', '');
  }

  // Vérifier si une chaîne est vide
  public static function isEmpty(?string $value): bool {
    return $value === null || trim($value) === '';
  }

  // Convertir une date au format d/m/Y vers Y-m-d
  public static function convertDateToDb(string $date): ?string {
    $d = \DateTime::createFromFormat('d/m/Y', $date);
    return $d ? $d->format('Y-m-d') : null;
  }

  // Convertir une date au format Y-m-d vers d/m/Y
  public static function convertDateFromDb(string $date): ?string {
    $d = \DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('d/m/Y') : null;
  }
}
