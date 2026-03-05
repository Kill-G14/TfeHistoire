<?php

namespace App\Models\ModelsDTO;

use App\Models\Favorite;

class FavoriteDTO {
  public int $id;
  public int $user_id;
  public int $event_id;
  public string $created_at;

  public function __construct(Favorite $favorite) {
    $this->id = $favorite->id;
    $this->user_id = $favorite->user_id;
    $this->event_id = $favorite->event_id;
    $this->created_at = $favorite->created_at;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'user_id' => $this->user_id,
      'event_id' => $this->event_id,
      'created_at' => $this->created_at
    ];
  }
}
