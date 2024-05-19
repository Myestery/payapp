<?php


namespace App\Payments;

use App\Models\Account;
use Exception;
use App\Models\User;
use App\Payments\PaymentData;

interface PaymentGateway
{
    public function getId(): string;

    public function initiatePayment(User $user, PaymentData $paymentData): InitiatePaymentResult;

    public function getTransactionData(string $paymentReference): TransactionData;

    public function getSettlementData(string $paymentReference): TransactionSettlementData;

    public function processWebhook(WebhookResource $webhookResource): WebhookResult;

    public function createVirtualAccount(
        User $user,
        Account $account
    ): VirtualAccountCreationResult;

}
