<?php

namespace App\Models;

class Ticket {
  public int $id;
  public int $event_id;
  public string $name;
  public ?string $description;
  public float $price;
  public int $quantity;
  public ?string $start_sale_date;
  public ?string $end_sale_date;
  public bool $is_deleted;
  public string $created_at;
  public string $updated_at;
}
