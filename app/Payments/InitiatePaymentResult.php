<?php

namespace App\Payments;

class InitiatePaymentResult
{
    public function __construct(
        public string $checkoutUrl,
        public ?string $transactionId = null)
    {
    }
}
