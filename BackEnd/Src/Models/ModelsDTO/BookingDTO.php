<?php

namespace App\Models\ModelsDTO;

use App\Models\Booking;

class BookingDTO {
  public int $id;
  public int $user_id;
  public int $event_id;
  public int $tickets_count;
  public float $total_price;
  public string $booking_status;
  public string $created_at;

  public function __construct(Booking $booking) {
    $this->id = $booking->id;
    $this->user_id = $booking->user_id;
    $this->event_id = $booking->event_id;
    $this->tickets_count = $booking->tickets_count;
    $this->total_price = $booking->total_price;
    $this->booking_status = $booking->booking_status;
    $this->created_at = $booking->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'event_id' => $this->event_id,
      'tickets_count' => $this->tickets_count,
      'total_price' => $this->total_price,
      'booking_status' => $this->booking_status,
      'created_at' => $this->created_at
    ];
  }
}
