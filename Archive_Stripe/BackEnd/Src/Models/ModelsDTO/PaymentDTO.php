<?php

namespace App\Models\ModelsDTO;

use App\Models\Payment;

class PaymentDTO {
    public int $id;
    public int $order_id;
    public ?string $stripe_payment_intent_id;
    public float $amount;
    public string $currency;
    public string $status;
    public ?string $payment_method;
    public ?string $receipt_url;
    public ?float $refund_amount;
    public ?string $refunded_at;
    public string $created_at;

    public function __construct(Payment $payment) {
        $this->id = $payment->id;
        $this->order_id = $payment->order_id;
        $this->stripe_payment_intent_id = $payment->stripe_payment_intent_id;
        $this->amount = $payment->amount;
        $this->currency = $payment->currency;
        $this->status = $payment->status;
        $this->payment_method = $payment->payment_method;
        $this->receipt_url = $payment->receipt_url;
        $this->refund_amount = $payment->refund_amount;
        $this->refunded_at = $payment->refunded_at;
        $this->created_at = $payment->created_at;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'stripe_payment_intent_id' => $this->stripe_payment_intent_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'receipt_url' => $this->receipt_url,
            'refund_amount' => $this->refund_amount,
            'refunded_at' => $this->refunded_at,
            'created_at' => $this->created_at,
        ];
    }
}
