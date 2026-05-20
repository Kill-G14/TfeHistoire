<?php

namespace App\Models;

class Reservation
{
    public int $id;
    public int $user_id;
    public int $event_id;
    public int $quantity;
    public string $status;
    public bool $is_deleted;
    public string $created_at;
    public string $updated_at;
}
