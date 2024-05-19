<?php


namespace App\Payments;

use Exception;
use App\Models\User;
use App\Models\Account;
use App\Payments\BankAccount;
use App\Payments\PaymentData;
use App\Payments\WithdrawalResult;

interface PaymentGateway
{
    public function getId(): string;

    public function initiatePayment(PaymentData $paymentData): InitiatePaymentResult;

    public function getTransactionData(string $paymentReference): TransactionData;

    public function getBanks(): array;

    public function resolveBankAccount(string $accountNumber, string $bankCode): BankAccount;

    public function getGL(): Account;

    public function processWebhook(WebhookResource $webhookResource): WebhookResult;

    public function createVirtualAccount(
        User $user,
        Account $account
    ): VirtualAccountCreationResult;

    public function createWithdrawal(
        string $bankCode,
        string $accountNumber,
        int $amount,
        string $reference
    ): WithdrawalResult;

}
