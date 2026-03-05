<?php

namespace App\Models\ModelsDTO;

use App\Models\OrderItem;

class OrderItemDTO {
  public int $id;
  public int $order_id;
  public int $ticket_id;
  public int $quantity;
  public float $unit_price;
  public float $subtotal;
  public string $created_at;

  public function __construct(OrderItem $orderItem) {
    $this->id = $orderItem->id;
    $this->order_id = $orderItem->order_id;
    $this->ticket_id = $orderItem->ticket_id;
    $this->quantity = $orderItem->quantity;
    $this->unit_price = $orderItem->unit_price;
    $this->subtotal = $orderItem->subtotal;
    $this->created_at = $orderItem->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'order_id' => $this->order_id,
      'ticket_id' => $this->ticket_id,
      'quantity' => $this->quantity,
      'unit_price' => $this->unit_price,
      'subtotal' => $this->subtotal,
      'created_at' => $this->created_at
    ];
  }
}
