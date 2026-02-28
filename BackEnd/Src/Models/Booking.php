<?php

namespace App\Models;

class Booking {
  public int $id;
  public int $user_id;
  public int $event_id;
  public int $tickets_count;
  public float $total_price;
  public string $booking_status;
  public string $created_at;
  public string $updated_at;
}
