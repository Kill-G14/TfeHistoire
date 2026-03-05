<?php

namespace App\Models\ModelsDTO;

use App\Models\Event;

class EventDTO {
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
  public bool $is_pending;
  public bool $is_approved;
  public bool $is_rejected;
  public ?string $image_url;
  public string $created_at;

  public function __construct(Event $event) {
    $this->id = $event->id;
    $this->user_id = $event->user_id;
    $this->title = $event->title;
    $this->description = $event->description;
    $this->country = $event->country;
    $this->city = $event->city;
    $this->postal_code = $event->postal_code;
    $this->address = $event->address;
    $this->latitude = $event->latitude;
    $this->longitude = $event->longitude;
    $this->date = $event->date;
    $this->time = $event->time;
    $this->category = $event->category;
    $this->is_free = $event->is_free;
    $this->is_pending = $event->is_pending;
    $this->is_approved = $event->is_approved;
    $this->is_rejected = $event->is_rejected;
    $this->image_url = $event->image_url;
    $this->created_at = $event->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'title' => $this->title,
      'description' => $this->description,
      'country' => $this->country,
      'city' => $this->city,
      'postal_code' => $this->postal_code,
      'address' => $this->address,
      'latitude' => $this->latitude,
      'longitude' => $this->longitude,
      'date' => $this->date,
      'time' => $this->time,
      'category' => $this->category,
      'is_free' => $this->is_free,
      'is_pending' => $this->is_pending,
      'is_approved' => $this->is_approved,
      'is_rejected' => $this->is_rejected,
      'image_url' => $this->image_url,
      'created_at' => $this->created_at
    ];
  }
}
