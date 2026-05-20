<?php

namespace App\Models;

class OrderItem {
  public int $id;
  public int $order_id;
  public int $event_id;
  public string $ticket_name;
  public int $quantity;
  public float $unit_price;
  public float $subtotal;
  public bool $is_deleted;
  public string $created_at;
}
