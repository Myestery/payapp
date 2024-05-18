<?php


namespace App\Payments;

use App\Models\Account;
use Exception;
use App\Models\User;
use App\Payments\PaymentData;
use Illuminate\Support\Carbon;
use App\Payments\TransactionData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Payments\CardTransactionData;
use App\Payments\InitiatePaymentResult;

class FlutterwaveGateway implements PaymentGateway
{
    const ID = 'flutterwave';

    public function __construct()
    {
    }

    /**
     * @param User $user
     * @param PaymentData $paymentData
     * @return InitiatePaymentResult
     */
    public function initiatePayment(User $user, PaymentData $paymentData): InitiatePaymentResult
    {
        $arr = [
            'tx_ref' => $paymentData->referenceCode,
            'amount' => $paymentData->totalAmount,
            'currency' => $paymentData->currency,
            'redirect_url' => $paymentData->redirectUrl,
            'customer' => [
                'email' => $paymentData->customerEmail,
                'name' => $paymentData->customerName,
                'phonenumber' => $paymentData->customerPhone ?? "",
            ],
            'customizations' => [
                'title' => 'Vendors.so',
                'description' => 'Vendors.so',
                'logo' => 'https://vendors.so/img/logo.svg',
            ],
            'meta' => $paymentData->meta ?? null,
        ];

        // dd($arr);

        $response = Http::withToken(config('services.flutterwave.secret'))
            ->withOptions(['debug' => config('app.debug')])
            ->acceptJson()
            ->post(config('services.flutterwave.url') . '/payments', $arr);

        if ($response->failed()) {
            throw new Exception('Flutterwave error: ' . $response->body());
        }
        return new InitiatePaymentResult($response->json('data.link'));
    }

    public function getTransactionData(string $paymentReference): TransactionData
    {
        $URL = config('services.flutterwave.url') . "/transactions/" . $paymentReference . "/verify";

        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->get($URL)->json();

        return $this->adaptTransactionData($responseBody);
    }

    public function getCardTransactionData(string $paymentReference): CardTransactionData
    {
        $responseBody = $this->getFullTransactionData($paymentReference);

        return $this->adaptCardTransactionData($responseBody);
    }

    public function getFullTransactionData(string $paymentReference)
    {
        $URL = config('services.flutterwave.url') . "/transactions/" . $paymentReference . "/verify";

        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->get($URL)->json();
        return $responseBody;
    }

    private function adaptCardTransactionData($tx): CardTransactionData
    {
        $data = $tx['data']['card'];
        return new CardTransactionData(
            first_6digits: $data['first_6digits'],
            last_4digits: $data['last_4digits'],
            issuer: $data['issuer'],
            country: $data['country'],
            type: $data['type'],
            token: $data['token'],
            expiry: $data['expiry'],
            processor_response: $tx['data']['processor_response'],
        );
    }

    private function adaptTransactionData($tx): TransactionData
    {
        $data = $tx['data'];
        return new TransactionData(
            amountPaid: $data['amount'],
            settlementAmount: $data['amount_settled'],
            paymentMethod: $this->adaptPaymentMethod($data['payment_type']),
            status: $this->adaptStatus($data['status']),
            internalTxId: $data['tx_ref'] ?? null,
            externalTxId: $data['id'] ?? null,
            paidOn: Carbon::parse($data['created_at']),
        );
    }

    private function adaptPaymentMethod($method): PaymentMethod|string
    {
        return match ($method) {
            'card' => PaymentMethod::CARD,
            'bank transfer' => PaymentMethod::BANK_TRANSFER,
            default => $method,
        };
    }

    private function adaptStatus($status): TransactionStatus
    {
        return match ($status) {
            'successful' => TransactionStatus::PAID(),
            default => TransactionStatus::FAILED(),
        };
    }

    public function getSettlementData(string $paymentReference): TransactionSettlementData
    {
        throw new Exception('getSettlementData not implemented for Flutterwave');
    }

    public function verifyWebhookPayload(string $paymentReference, string $amountPaid, string $paidOn, string $transactionReference): string
    {
        return '';
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function createVirtualAccount(User $user, Account $account): VirtualAccountCreationResult
    {
        throw new Exception('createVirtualAccount not implemented for Flutterwave');
    }

}
