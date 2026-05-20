<?php

namespace App\Models\ModelsDTO;

use App\Models\Ticket;

class TicketDTO {
  public int $id;
  public int $event_id;
  public string $name;
  public ?string $description;
  public float $price;
  public int $quantity;
  public ?string $start_sale_date;
  public ?string $end_sale_date;
  public string $created_at;

  public function __construct(Ticket $ticket) {
    $this->id = $ticket->id;
    $this->event_id = $ticket->event_id;
    $this->name = $ticket->name;
    $this->description = $ticket->description;
    $this->price = $ticket->price;
    $this->quantity = $ticket->quantity;
    $this->start_sale_date = $ticket->start_sale_date;
    $this->end_sale_date = $ticket->end_sale_date;
    $this->created_at = $ticket->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'event_id' => $this->event_id,
      'name' => $this->name,
      'description' => $this->description,
      'price' => $this->price,
      'quantity' => $this->quantity,
      'start_sale_date' => $this->start_sale_date,
      'end_sale_date' => $this->end_sale_date,
      'created_at' => $this->created_at
    ];
  }
}
