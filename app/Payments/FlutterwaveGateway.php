<?php


namespace App\Payments;

use Exception;
use App\Models\User;
use App\Wallet\Ledger;
use App\Models\Account;
use Illuminate\Support\Str;
use App\Payments\PaymentData;
use Illuminate\Support\Carbon;
use App\Payments\TransactionData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Payments\InitiatePaymentResult;

class FlutterwaveGateway implements PaymentGateway
{
    const ID = 'flutterwave';

    public function __construct()
    {
    }

    public function getGL(): Account
    {
        return Account::where('name', 'FLUTTERWAVE GL')->first();
    }

    /**
     * @param User $user
     * @param PaymentData $paymentData
     * @return InitiatePaymentResult
     */
    public function initiatePayment(PaymentData $paymentData): InitiatePaymentResult
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
                'title' => 'Payment App',
                'description' => 'One time payment for a service',
                'logo' => 'https://camo.githubusercontent.com/043cf668c8a293c0d461d2a118dec5920a69d2811bc0e91068e8299712ce8f48/68747470733a2f2f7777772e7061792e6e6c2f68756266732f32353735383235302f696d616765732f5061792532304c6f676f2532302d2532305247425f5072696d6172792532304c6f676f2e706e673f743d31363538313439393330333330',
            ],
            'meta' => $paymentData->meta ?? null,
        ];

        // dd($arr);

        $response = Http::withToken(config('services.flutterwave.secret'))
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
            customerEmail: $data['customer']['email'],
            destinationAccount: $data['account_number'] ?? null,
            fee: $data['amount'] - $data['amount_settled'],
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
            'successful' => TransactionStatus::PAID,
            default => TransactionStatus::FAILED,
        };
    }

    public function getId(): string
    {
        return self::ID;
    }

    public function createVirtualAccount(User $user, Account $account): VirtualAccountCreationResult
    {
        throw new Exception('createVirtualAccount not implemented for Flutterwave');
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
        $tx = $this->getTransactionData($webhookResource->data['id']);

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
       $secret = config('services.flutterwave.secret');
         $response = Http::withToken($secret)
                ->get(config('services.flutterwave.url') . '/banks/NG');

          if ($response->failed()) {
                throw new Exception('Flutterwave error: ' . $response->body());
          }

          return $response->json('data');
    }

    public function resolveBankAccount(string $accountNumber, string $bankCode): BankAccount
    {
        $secret = config('services.flutterwave.secret');
        $response = Http::withToken($secret)
            ->get(config('services.flutterwave.url') . '/accounts/resolve', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

        if ($response->failed()) {
            throw new Exception('Flutterwave error: ' . $response->body());
        }

        $data = $response->json('data');
        return new BankAccount(
            accountNumber: $data['account_number'],
            bankCode: $data['bank_code'],
            accountName: $data['account_name'],
            bankName: $data['bank_name'],
        );
    }

}
