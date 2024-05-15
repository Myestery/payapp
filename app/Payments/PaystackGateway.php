<?php


namespace App\Payments;

use App\Models\Shop;
use Illuminate\Support\Collection;
use Unicodeveloper\Paystack\Facades\Paystack;

class PaystackGateway implements PaymentGateway
{
    const ID = 'paystack';

    /**
     * PaystackGateway constructor.
     */
    public function __construct()
    {
    }

    public function initiatePayment(Shop $shop, PaymentData $paymentData): InitiatePaymentResult
    {
        $data = [
            "first_name" => $paymentData->customerName,
            'email' => $paymentData->customerEmail,
            'orderID' => $paymentData->orderId,
            'amount' => $paymentData->totalAmount * 100,
            'currency' => $paymentData->currency,
            'reference' => $paymentData->referenceCode,
            'subaccount' => $paymentData->subAccountCode,
            'callback_url' => $paymentData->redirectUrl,
        ];
        $result = Paystack::getAuthorizationUrl($data);
        return new InitiatePaymentResult($result->url);
    }

    function getTransactionData(string $paymentReference): TransactionData
    {
        throw new \Exception('Not yet implemented');
    }

    public function getSettlementData(string $paymentReference): TransactionSettlementData
    {
        throw new \Exception('getSettlementData not implemented for Paystack');
    }

    function createSubAccount(string $bankCode, string $accountNumber, string $splitPercentage, string $email, string $accountName, string $currencyCode = null): SubaccountCreationResult
    {
        throw new \Exception('Not yet implemented');
    }

    public function verifyWebhookPayload(string $paymentReference, string $amountPaid, string $paidOn, string $transactionReference): string
    {
        throw new \Exception('Not yet implemented');
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function getSubaccounts(): Collection
    {
        throw new \Exception('Not yet implemented');
    }
}
