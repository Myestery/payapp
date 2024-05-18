<?php


namespace App\Payments;

use App\Models\Account;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
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

    public function initiatePayment(User $user, PaymentData $paymentData): InitiatePaymentResult
    {
        $data = [
            "first_name" => $paymentData->customerName,
            'email' => $paymentData->customerEmail,
            // 'orderID' => $paymentData->orderId,
            'amount' => $paymentData->totalAmount * 100,
            'currency' => $paymentData->currency,
            'reference' => $paymentData->referenceCode,
            // 'subaccount' => $paymentData->subAccountCode,
            'callback_url' => $paymentData->redirectUrl,
        ];
        $result = Paystack::getAuthorizationUrl($data);
        return new InitiatePaymentResult($result->url);
    }

    function getTransactionData(string $paymentReference): TransactionData
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

    public function getSettlementData(string $paymentReference): TransactionSettlementData
    {
        throw new \Exception('Not yet implemented');
    }

    public function createVirtualAccount(User $user, Account $account): VirtualAccountCreationResult
    {
        // create a customer
        $customer = $this->createCustomer($user);
        // create a dedicated account for the customer
        $dedicatedAccount = $this->createDedicatedAccount($customer);
        // return the account details
        $v_account = VirtualAccountCreationResult::fromArray([
            'account_id' => $account->id,
            'bank_code' => $dedicatedAccount['bank']['id'],
            'account_name' => $dedicatedAccount['account_name'],
            'account_number' => $dedicatedAccount['account_number'],
            'bank_name' => $dedicatedAccount['bank']['name'],
            'provider' => self::ID,
            'provider_data' => [
                "customer_code" => $dedicatedAccount['customer']['customer_code'],
                "account_type" => $dedicatedAccount['assignment']['account_type'],
            ],
            'is_active' => $dedicatedAccount['active'],
            'activated_at' => $dedicatedAccount['assignment']['assigned_at'],
        ]);

        return $v_account;

    }

    /**
     * @param User $user
     * @return array
     * @throws \Exception
     */
    protected function createCustomer(User $user)
    {
        $data = [
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
        ];
        $result = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('paystack.secretKey'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/customer', $data);

        // check for success
        if (!$result->ok()) {
            throw new \Exception('Failed to create customer on Paystack');
        }

        return $result->json()['data'];
    }

    protected function createDedicatedAccount(array $customer)
    {
        $data = [
            'customer' => $customer['customer_code'],
            // hardcoded cos we will never go live
            'preferred_bank' => 'test-bank',
        ];
        $result = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('paystack.secretKey'),
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/dedicated_account', $data);

        // check for success
        if (!$result->ok()) {
            throw new \Exception('Failed to create dedicated account on Paystack: ' + $result->body());
        }

        return $result->json()['data'];
    }

    public function processWebhook(WebhookResource $webhookResource): WebhookResult
    {
        throw new Exception('processWebhook not implemented for Flutterwave');
    }
}
