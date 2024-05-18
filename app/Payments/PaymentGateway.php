<?php


namespace App\Payments;

use Exception;
use App\Models\User;
use App\Payments\PaymentData;

interface PaymentGateway
{
    public function getId(): string;

    public function initiatePayment(User $user, PaymentData $paymentData): InitiatePaymentResult;

    public function getTransactionData(string $paymentReference): TransactionData;

    public function getCardTransactionData(string $paymentReference): CardTransactionData;

    public function getSettlementData(string $paymentReference): TransactionSettlementData;

    public function verifyWebhookPayload(string $paymentReference, string $amountPaid, string $paidOn, string $transactionReference): string;

    public function createVirtualAccount(
        User $user,
    ): VirtualAccountCreationResult;
}
