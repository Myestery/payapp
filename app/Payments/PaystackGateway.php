<?php


namespace App\Payments;

use Exception;
use App\Models\Shop;
use App\Models\User;
use App\Wallet\Ledger;
use App\Models\Account;
use App\Wallet\WalletConst;
use Illuminate\Support\Str;
use App\Payments\PaymentData;
use App\Wallet\WalletService;
use Illuminate\Support\Carbon;
use App\Payments\PaymentMethod;
use App\Payments\WebhookResult;
use App\Models\WalletTransaction;
use App\Payments\TransactionData;
use App\Payments\WebhookResource;
use Illuminate\Support\Collection;
use App\Payments\TransactionStatus;
use Illuminate\Support\Facades\Http;
use App\Payments\InitiatePaymentResult;
use Unicodeveloper\Paystack\Facades\Paystack;
use App\Payments\VirtualAccountCreationResult;

class PaystackGateway implements PaymentGateway
{
    const ID = 'paystack';

    /**
     * PaystackGateway constructor.
     */
    public function __construct()
    {
    }

    public function getGL(): Account
    {
        return Account::where('name', 'PAYSTACK GL')->first();
    }

    public function initiatePayment(PaymentData $paymentData): InitiatePaymentResult
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

    public function getTransactionData(string $paymentReference): TransactionData
    {
        $URL = "https://api.paystack.co/transaction/verify/" . $paymentReference;

        $responseBody = Http::withToken(config("paystack.secretKey"))
            ->get($URL)->json();

        return $this->adaptTransactionData($responseBody);
    }

    private function adaptTransactionData($tx): TransactionData
    {
        $data = $tx['data'];
        return new TransactionData(
            amountPaid: $data['amount'] / 100,
            settlementAmount: $data['amount'] / 100,
            paymentMethod: $this->adaptPaymentMethod($data['channel']),
            status: $this->adaptStatus($data['status']),
            internalTxId: $data['reference'] ?? null,
            externalTxId: $data['id'] ?? null,
            paidOn: Carbon::parse($data['createdAt']),
            customerEmail: $data['customer']['email'],
            destinationAccount: $data['metadata']['receiver_account_number'] ?? null,
            fee: $data['fees'] / 100,
        );
    }

    private function adaptPaymentMethod($method): PaymentMethod|string
    {
        return match ($method) {
            'card' => PaymentMethod::CARD,
            'dedicated_nuban' => PaymentMethod::BANK_TRANSFER,
            default => $method,
        };
    }

    private function adaptStatus($status): TransactionStatus
    {
        return match ($status) {
            'success' => TransactionStatus::PAID,
            'pending' => TransactionStatus::PENDING,
            default => TransactionStatus::FAILED,
        };
    }


    public function getId(): string
    {
        return self::ID;
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
        $tx = $this->verifyTransaction($webhookResource);
        // check if we have processed this transaction before
        $haveProcessed = $this->checkIfTransactionIsInDatabase($tx);
        // if we have, return

        if ($haveProcessed) {
            return new WebhookResult(
                account_id: "",
                amount: $tx->amountPaid,
                successful: false,
            );
        }

        $account = $tx->getAccountFromTx();

        // if we have not, process the transaction
        return $this->processTransaction($tx, $account);
    }

    private function verifyTransaction(WebhookResource $webhookResource): TransactionData
    {
        $tx = $this->getTransactionData($webhookResource->data['reference']);

        if ($tx->status !== TransactionStatus::PAID) {
            throw new Exception('Transaction not successful');
        }

        return $tx;
    }

    private function checkIfTransactionIsInDatabase(TransactionData $tx): bool
    {
        $transaction = \App\Models\WalletTransaction::where([
            ['provider_reference', $tx->externalTxId],
            ['provider', $this->getId()],
            ['status', '!=', WalletConst::PENDING],
        ])->first();

        if ($transaction) {
            return true;
        }
        return false;
    }

    private function processTransaction(TransactionData $tx, Account $account): WebhookResult
    {
        //  create a ledger and apply credit
        /** @var \App\Wallet\WalletService */
        $walletService = app()->make(\App\Wallet\WalletService::class);

        $gl = $this->getGL();
        $ref = Str::uuid();
        $amount = $tx->amountPaid;
        $narration = "PAYIN/" . $ref . " on " . $tx->paidOn->format('Y-m-d');
        $category = Str::upper(Str::slug($tx->paymentMethod->value . " PAYIN", "_"));

        $ledgers = [
            new Ledger(
                action: WalletConst::CREDIT,
                account_id: $account->id,
                amount: $amount,
                narration: $narration,
                category: $category,
            ),
            new Ledger(
                action: WalletConst::DEBIT,
                account_id: $gl->id,
                amount: $tx->amountPaid,
                narration: $narration,
                category: $category,
            ),
        ];

        $result = $walletService->post(
            reference: $ref,
            total_amount: $amount,
            ledgers: $ledgers,
            provider_reference: $tx->externalTxId,
            provider: $this->getId(),
        );

        if ($result->isSuccessful()) {
            return new WebhookResult(
                account_id: $account->account_id,
                amount: $tx->amountPaid,
                successful: true,
            );
        } else {
            return new WebhookResult(
                account_id: $account->account_id,
                amount: $tx->amountPaid,
                successful: false,
            );
        }
    }

    public function getBanks(): array
    {
        $response = Http::withToken(config('paystack.secretKey'))
            ->get('https://api.paystack.co/bank');

        $banks = $response->json()['data'];

        return collect($banks)->map(function ($bank) {
            return [
                'code' => $bank['code'],
                'name' => $bank['name'],
            ];
        })->toArray();
    }

    public function resolveBankAccount(string $accountNumber, string $bankCode): BankAccount
    {
        $response = Http::withToken(config('paystack.secretKey'))
            ->get('https://api.paystack.co/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

        $data = $response->json()['data'];
        $banks = $this->getBanks();

        return new BankAccount(
            accountName: $data['account_name'],
            accountNumber: $data['account_number'],
            bankName: collect($banks)->firstWhere('code', $bankCode)['name'],
            bankCode: $bankCode,
        );
    }


}
