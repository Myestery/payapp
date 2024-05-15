<?php


namespace App\Payments;


class PaymentData
{
    public function __construct(
        public string $customerName,
        public string $currency,
        public string $orderId,
        public string $customerEmail,
        public string $referenceCode,
        public string $redirectUrl,
        public float $totalAmount,
        public ?string $customerPhone,
        public string $paymentDescription = '',
        public ?float $vendorAmount = null,
        public ?float $vendorPercentage = null,
        public ?string $method = null,
        public ?string $planId = null,
        public $meta = []
    ) {
    }

}
