<?php

namespace App\Models\ModelsDTO;

use App\Models\Reservation;

class ReservationDTO {
  public int $id;
  public int $user_id;
  public int $event_id;
  public int $quantity;
  public string $status;
  public string $created_at;
  public ?string $updated_at;
  
  // Informations de l'événement (optionnelles, depuis JOIN)
  public ?string $event_title;
  public ?string $event_date;
  public ?string $event_time;
  public ?string $event_city;
  public ?string $event_address;
  public ?string $event_image;
  public ?bool $event_is_free;
  public ?float $event_ticket_price;

  public function __construct($reservation) {
    // Si c'est un objet Reservation
    if ($reservation instanceof Reservation) {
      $this->id = $reservation->id;
      $this->user_id = $reservation->user_id;
      $this->event_id = $reservation->event_id;
      $this->quantity = $reservation->quantity;
      $this->status = $reservation->status;
      $this->created_at = $reservation->created_at;
      $this->updated_at = $reservation->updated_at ?? null;
      
      // Pas d'informations d'événement depuis un objet simple
      $this->event_title = null;
      $this->event_date = null;
      $this->event_time = null;
      $this->event_city = null;
      $this->event_address = null;
      $this->event_image = null;
      $this->event_is_free = null;
      $this->event_ticket_price = null;
    }
    // Si c'est un tableau associatif (depuis repository avec JOIN)
    else if (is_array($reservation)) {
      $this->id = $reservation['id'];
      $this->user_id = $reservation['user_id'];
      $this->event_id = $reservation['event_id'];
      $this->quantity = $reservation['quantity'];
      $this->status = $reservation['status'];
      $this->created_at = $reservation['created_at'];
      $this->updated_at = $reservation['updated_at'] ?? null;
      
      // Informations de l'événement (depuis JOIN)
      $this->event_title = $reservation['event_title'] ?? null;
      $this->event_date = $reservation['event_date'] ?? null;
      $this->event_time = $reservation['event_time'] ?? null;
      $this->event_city = $reservation['event_city'] ?? null;
      $this->event_address = $reservation['event_address'] ?? null;
      $this->event_image = $reservation['event_image'] ?? null;
      $this->event_is_free = isset($reservation['event_is_free']) ? (bool)$reservation['event_is_free'] : null;
      $this->event_ticket_price = isset($reservation['event_ticket_price']) ? (float)$reservation['event_ticket_price'] : null;
    }
  }

  public function toArray(): array {
    $data = [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'event_id' => $this->event_id,
      'quantity' => $this->quantity,
      'status' => $this->status,
      'created_at' => $this->created_at
    ];

    // Ajouter updated_at si présent
    if ($this->updated_at !== null) {
      $data['updated_at'] = $this->updated_at;
    }

    // Ajouter les informations de l'événement si présentes
    if ($this->event_title !== null) {
      $data['event_title'] = $this->event_title;
      $data['event_date'] = $this->event_date;
      $data['event_time'] = $this->event_time;
      $data['event_city'] = $this->event_city;
      $data['event_address'] = $this->event_address;
      $data['event_image'] = $this->event_image;
      $data['event_is_free'] = $this->event_is_free;
      $data['event_ticket_price'] = $this->event_ticket_price;
    }

    return $data;
  }
}
