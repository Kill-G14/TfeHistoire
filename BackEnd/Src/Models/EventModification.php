<?php

namespace App\Models;

class EventModification {
  public int $id;
  public int $event_id;
  public string $new_date;
  public string $new_time;
  public string $old_date;
  public string $old_time;
  public string $status;
  public ?string $rejection_reason;
  public string $created_at;
  public ?string $validated_at;
}
