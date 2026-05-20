<?php

namespace App\Models\ModelsDTO;

use App\Models\TicketGenerated;

class TicketGeneratedDTO {
  public int $id;
  public int $order_item_id;
  public string $qr_code;
  public string $unique_code;
  public bool $is_used;
  public ?string $used_at;
  public string $created_at;

  public function __construct(TicketGenerated $ticketGenerated) {
    $this->id = $ticketGenerated->id;
    $this->order_item_id = $ticketGenerated->order_item_id;
    $this->qr_code = $ticketGenerated->qr_code;
    $this->unique_code = $ticketGenerated->unique_code;
    $this->is_used = $ticketGenerated->is_used;
    $this->used_at = $ticketGenerated->used_at;
    $this->created_at = $ticketGenerated->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'order_item_id' => $this->order_item_id,
      'qr_code' => $this->qr_code,
      'unique_code' => $this->unique_code,
      'is_used' => $this->is_used,
      'used_at' => $this->used_at,
      'created_at' => $this->created_at
    ];
  }
}
