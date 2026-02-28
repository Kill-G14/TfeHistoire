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
  public string $date;
  public string $time;
  public float $price;
  public string $category;
  public int $available_tickets;
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
    $this->date = $event->date;
    $this->time = $event->time;
    $this->price = $event->price;
    $this->category = $event->category;
    $this->available_tickets = $event->available_tickets;
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
      'date' => $this->date,
      'time' => $this->time,
      'price' => $this->price,
      'category' => $this->category,
      'available_tickets' => $this->available_tickets,
      'image_url' => $this->image_url,
      'created_at' => $this->created_at
    ];
  }
}
