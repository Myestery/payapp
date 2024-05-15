<?php


namespace App\Payments;

use DateTime;

class TransactionData
{
    public function __construct(
        public float     $amountPaid,
        public float     $settlementAmount,
        public PaymentMethod|string    $paymentMethod,
        public TransactionStatus    $status,
        public ?string   $internalTxId = null,
        public ?string   $externalTxId = null,
        public ?DateTime $paidOn = null,
    ) {
    }
}
