<?php


namespace App\Payments;


// use App\Models\Shop;
// use App\Payments\Exceptions\ExistingSubAccountException;
// use App\Payments\Exceptions\InvalidAccountDetailsException;
use Exception;
use App\Models\User;
use App\Payments\PaymentData;
use Illuminate\Support\Collection;

interface PaymentGateway
{
    public function getId(): string;

    public function initiatePayment(User $user, PaymentData $paymentData): InitiatePaymentResult;

    public function getTransactionData(string $paymentReference): TransactionData;

    public function getCardTransactionData(string $paymentReference): CardTransactionData;

    public function getSettlementData(string $paymentReference): TransactionSettlementData;

    public function getSubaccounts(): Collection;

    public function verifyWebhookPayload(string $paymentReference, string $amountPaid, string $paidOn, string $transactionReference): string;

    /**
     * @throws Exception
     */
    public function createSubAccount(
        string $bankCode,
        string $accountNumber,
        string $splitPercentage,
        string $email,
        string $accountName,
        string $currencyCode = null
    ): SubaccountCreationResult;
}
