<?php

namespace App\Models\ModelsDTO;

use App\Models\Order;

class OrderDTO {
  public int $id;
  public int $user_id;
  public float $total_price;
  public bool $is_pending;
  public bool $is_paid;
  public bool $is_failed;
  public bool $is_cancelled;
  public string $payment_provider;
  public ?string $payment_id;
  public string $created_at;
  public array $items;

  public function __construct(Order $order, array $items = []) {
    $this->id = $order->id;
    $this->user_id = $order->user_id;
    $this->total_price = $order->total_price;
    $this->is_pending = $order->is_pending;
    $this->is_paid = $order->is_paid;
    $this->is_failed = $order->is_failed;
    $this->is_cancelled = $order->is_cancelled;
    $this->payment_provider = $order->payment_provider;
    $this->payment_id = $order->payment_id;
    $this->created_at = $order->created_at;
    $this->items = $items;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'total_price' => $this->total_price,
      'is_pending' => $this->is_pending,
      'is_paid' => $this->is_paid,
      'is_failed' => $this->is_failed,
      'is_cancelled' => $this->is_cancelled,
      'payment_provider' => $this->payment_provider,
      'payment_id' => $this->payment_id,
      'created_at' => $this->created_at,
      'items' => $this->items
    ];
  }
}
