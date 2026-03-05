<?php

namespace App\Models;

class Favorite {
  public int $id;
  public int $user_id;
  public int $event_id;
  public bool $is_deleted;
  public string $created_at;
}
