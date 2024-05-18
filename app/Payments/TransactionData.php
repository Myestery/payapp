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
        public ?string   $customerEmail = null,
        public ?string   $destinationAccount = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'amountPaid' => $this->amountPaid,
            'settlementAmount' => $this->settlementAmount,
            'paymentMethod' => $this->paymentMethod,
            'status' => $this->status,
            'internalTxId' => $this->internalTxId,
            'externalTxId' => $this->externalTxId,
            'paidOn' => $this->paidOn,
        ];
    }
}
