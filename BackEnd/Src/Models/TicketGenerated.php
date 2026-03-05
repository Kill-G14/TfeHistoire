<?php

namespace App\Models;

class TicketGenerated {
  public int $id;
  public int $order_item_id;
  public string $qr_code;
  public string $unique_code;
  public bool $is_used;
  public ?string $used_at;
  public bool $is_deleted;
  public string $created_at;
}
