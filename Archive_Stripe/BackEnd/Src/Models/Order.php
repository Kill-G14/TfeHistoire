<?php

namespace App\Models;

class Order {
  public int $id;
  public int $user_id;
  public float $total_price;
  public bool $is_pending;
  public bool $is_paid;
  public bool $is_failed;
  public bool $is_cancelled;
  public string $payment_provider;
  public ?string $payment_id;
  public bool $is_deleted;
  public string $created_at;
  public string $updated_at;
}
