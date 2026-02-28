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
  public string $date;
  public string $time;
  public float $price;
  public string $category;
  public int $available_tickets;
  public ?string $image_url;
  public string $created_at;
  public string $updated_at;
}
