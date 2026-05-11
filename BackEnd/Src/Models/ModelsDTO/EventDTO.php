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
  public bool $has_pending_modification;
  public bool $deletion_requested;
  public ?string $deletion_message;
  public ?string $deletion_requested_at;
  public ?string $image_event;
  public string $created_at;
  
  // Informations de billetterie
  public ?int $ticket_id;
  public ?float $ticket_price;
  public ?int $ticket_quantity;

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
    $this->has_pending_modification = $event->has_pending_modification;
    $this->deletion_requested = $event->deletion_requested;
    $this->deletion_message = $event->deletion_message;
    $this->deletion_requested_at = $event->deletion_requested_at;
    $this->image_event = $event->image_event;
    $this->created_at = $event->created_at;
    
    // Informations de billetterie
    $this->ticket_id = $event->ticket_id ?? null;
    $this->ticket_price = $event->ticket_price ?? null;
    $this->ticket_quantity = $event->ticket_quantity ?? null;
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
      'has_pending_modification' => $this->has_pending_modification,
      'deletion_requested' => $this->deletion_requested,
      'deletion_message' => $this->deletion_message,
      'deletion_requested_at' => $this->deletion_requested_at,
      'image_event' => $this->image_event,
      'created_at' => $this->created_at,
      // Informations de billetterie
      'ticket_id' => $this->ticket_id,
      'ticket_price' => $this->ticket_price,
      'ticket_quantity' => $this->ticket_quantity
    ];
  }
}
