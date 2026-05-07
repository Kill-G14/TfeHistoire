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
  public bool $is_free;
  public float $ticket_price;
  public int $ticket_quantity;
  public bool $requires_stripe_account;
  public bool $stripe_account_verified;
  public bool $is_pending;
  public bool $is_approved;
  public bool $is_rejected;
  public bool $is_deleted;
  public ?string $image_event;
  public string $created_at;
  public string $updated_at;
}
