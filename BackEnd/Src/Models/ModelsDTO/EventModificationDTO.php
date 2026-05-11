<?php

namespace App\Models\ModelsDTO;

use App\Models\EventModification;

class EventModificationDTO {
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

  public function __construct(EventModification $modification) {
    $this->id = $modification->id;
    $this->event_id = $modification->event_id;
    $this->new_date = $modification->new_date;
    $this->new_time = $modification->new_time;
    $this->old_date = $modification->old_date;
    $this->old_time = $modification->old_time;
    $this->status = $modification->status;
    $this->rejection_reason = $modification->rejection_reason;
    $this->created_at = $modification->created_at;
    $this->validated_at = $modification->validated_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'event_id' => $this->event_id,
      'new_date' => $this->new_date,
      'new_time' => $this->new_time,
      'old_date' => $this->old_date,
      'old_time' => $this->old_time,
      'status' => $this->status,
      'rejection_reason' => $this->rejection_reason,
      'created_at' => $this->created_at,
      'validated_at' => $this->validated_at
    ];
  }
}
