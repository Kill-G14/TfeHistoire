<?php

namespace App\Models;

class Reservation
{
    public int $id;
    public int $user_id;
    public int $event_id;
    public int $quantity;
    public string $status;
    // not_required | pending | paid | failed | expired
    public string $payment_status = 'not_required';
    public ?string $stripe_session_id = null;
    public ?float $amount_total = null;
    public ?string $paid_at = null;
    public bool $is_deleted;
    public string $created_at;
    public string $updated_at;
}
