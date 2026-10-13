<?php

namespace App\Models;

class Event {
  public int $id;
  public int $user_id;
  public string $title;
  public string $description;
  public string $country;
  public string $city;
  public string $postal_code;
  public string $address;
  public ?float $latitude;
  public ?float $longitude;
  public string $date;
  public string $time;
  public string $category;
  public int $ticket_quantity;
  // Prix unitaire en EUR (0 = gratuit)
  public float $price = 0.0;
  // Nom de l'organisateur (colonne calculée par les requêtes de lecture, absente de la table events)
  public ?string $organizer_name = null;
  public bool $is_pending;
  public bool $is_approved;
  public bool $is_rejected;
  public bool $is_deleted;
  public bool $has_pending_modification;
  public bool $deletion_requested;
  public ?string $deletion_message;
  public ?string $deletion_requested_at;
  public ?string $image_event;
  public string $created_at;
  public string $updated_at;
}
