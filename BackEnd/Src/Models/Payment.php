<?php

namespace App\Models;

class Payment {
    public int $id;
    public int $order_id;
    public ?string $stripe_payment_intent_id;
    public ?string $stripe_checkout_session_id;
    public float $amount;
    public string $currency;
    public string $status;
    public ?string $payment_method;
    public ?string $receipt_url;
    public ?string $refund_id;
    public ?float $refund_amount;
    public ?string $refunded_at;
    public ?string $metadata;
    public ?string $error_message;
    public string $created_at;
    public string $updated_at;
}
