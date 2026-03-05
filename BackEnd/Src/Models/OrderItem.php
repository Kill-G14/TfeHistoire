<?php

namespace App\Models;

class OrderItem {
  public int $id;
  public int $order_id;
  public int $ticket_id;
  public int $quantity;
  public float $unit_price;
  public float $subtotal;
  public bool $is_deleted;
  public string $created_at;
}
