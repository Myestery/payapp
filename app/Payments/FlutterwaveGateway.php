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

    public function processWebhook(WebhookResource $webhookResource): WebhookResult
    {
        // queue this job
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

        $account = $this->getAccountFromTx($tx);

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
        $amount = $tx->amountPaid;
        $narration = "PAYIN/" . $tx->internalTxId . " on " . $tx->paidOn->format('Y-m-d');
        $category = Str::upper(Str::slug($tx->paymentMethod . " PAYIN", "_"));

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
            reference: $tx->internalTxId,
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

    private function getAccountFromTx(TransactionData $tx): Account
    {
        // for card payments, we can use the email to get the account
        if ($tx->paymentMethod === PaymentMethod::CARD) {
            $account = Account::where('email', $tx->customerEmail)->first();
        } else {
            // for bank transfers,
            $vAccount = \App\Models\VirtualAccount::where('account_number', $tx->destinationAccount)->first();
            $account = $vAccount->account;
        }

        if (!$account) {
            throw new Exception('Account not found');
        }

        return $account;
    }
}
