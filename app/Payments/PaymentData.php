<?php


namespace App\Payments;


class PaymentData
{
    public function __construct(
        public string $customerName,
        public string $currency,
        public string $customerEmail,
        public string $referenceCode,
        public ?string $redirectUrl,
        public float $totalAmount,
        public ?string $customerPhone,
        public string $paymentDescription = '',
        public ?string $method = null,
        public $meta = []
    ) {
    }

}
