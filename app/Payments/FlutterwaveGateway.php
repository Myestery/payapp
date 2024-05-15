<?php


namespace App\Payments;


use Exception;
use Throwable;
use App\Models\Shop;
use App\Models\User;
use App\Payments\PaymentData;
use Illuminate\Support\Carbon;
use App\Payments\TransactionData;
use Illuminate\Support\Collection;
use App\Helpers\GoodMonnifyAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Expr\Instanceof_;
use App\Payments\CardTransactionData;
use Illuminate\Support\Facades\Cache;
use App\Payments\InitiatePaymentResult;
use App\Exceptions\PaymentTypeNotSupported;
use App\Payments\Exceptions\ExistingSubAccountException;
use App\Payments\Exceptions\InvalidAccountDetailsException;

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
            'payment_plan' => $paymentData->planId ?? null,
            'payment_options' => $paymentData->planId ? 'card' : null,
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

    /**
     * @throws InvalidAccountDetailsException | ExistingSubAccountException | Exception
     */
    public function createSubAccount(
        string $bankCode,
        string $accountNumber,
        string $email,
        string $accountName,
        string $splitPercentage = "0",
        string $currencyCode = null
    ): SubaccountCreationResult {
        $result = Http::withToken(config('services.flutterwave.secret'))
            ->withOptions(['debug' => config('app.debug')])
            ->acceptJson()
            ->post(config('services.flutterwave.url') . '/subaccounts', [
                'account_bank' => $bankCode,
                'account_number' => $accountNumber,
                'business_email' => $email,
                'business_name' => $accountName,
                'country' => $currencyCode ?? "NG",
                // we take 5% of the amount as our fee
                // for some reason, 95 is whats coming here, so we change it to 0.05
                'split_value' => round(1 - ($splitPercentage / 100), 2),
                "split_type" => "percentage",
            ]);

        if ($result->failed()) {

            $error = json_decode($result->body());

            Log::debug('FlutterwaveGateway SubAccount Creation Error', ['message' => $error->message]);

            throw $this->mapError($error->message);
        }

        return new SubaccountCreationResult(
            subAccountCode: $result->json('data.subaccount_id'),
            accountName: $result->json('data.full_name'),
        );
    }

    /**
     * @throws InvalidAccountDetailsException | ExistingSubAccountException | Exception
     */
    public function createSubAccountForCustomer(
        string $bankCode,
        string $accountNumber,
        string $email,
        string $accountName,
        string $splitPercentage = "0",
        string $currencyCode = null
    ): SubaccountCreationResult {
        $result = Http::withToken(config('services.flutterwave.secret'))
            ->withOptions(['debug' => config('app.debug')])
            ->acceptJson()
            ->post(config('services.flutterwave.url') . '/subaccounts', [
                'account_bank' => $bankCode,
                'account_number' => $accountNumber,
                'business_email' => $email,
                'business_name' => $accountName,
                'country' => $currencyCode ?? "NG",
                'split_value' => $splitPercentage,
                "split_type" => "percentage",
            ]);

        if ($result->failed()) {

            $error = json_decode($result->body());

            Log::debug('FlutterwaveGateway SubAccount Creation Error', ['message' => $error->message]);

            throw $this->mapError($error->message);
        }

        return new SubaccountCreationResult(
            subAccountCode: $result->json('data.subaccount_id'),
            accountName: $result->json('data.full_name'),
        );
    }


    public function getSubaccounts(): Collection
    {
        $URL = config('services.flutterwave.url') . "/subaccounts";
        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->get($URL)->json();

        //        Log::debug("hello", $responseBody["meta"]);
        //        TODO fetch all pages

        return collect($responseBody["data"])->map(function ($subaccount) {
            return new SubaccountData(
                subAccountCode: $subaccount["subaccount_id"],
                accountNumber: $subaccount["account_number"],
                accountName: $subaccount["full_name"],
                currencyCode: "",
                country: $subaccount["country"],
                bankCode: $subaccount["account_bank"],
                externalId: $subaccount["id"],
                paymentProcessor: self::ID,
                businessName: $subaccount["business_name"],
                createdAt: Carbon::parse($subaccount['created_at']),
            );
        });
    }

    public function verifyWebhookPayload(string $paymentReference, string $amountPaid, string $paidOn, string $transactionReference): string
    {
        return '';
    }

    public function getId(): string
    {
        return self::ID;
    }

    private function mapError($message): Exception
    {
        if (str_contains($message, "Sorry we couldn't verify your account number"))
            return new InvalidAccountDetailsException();

        if (str_contains($message, "A subaccount with the account number and bank already exists"))
            return new ExistingSubAccountException();

        return new Exception($message);
    }

    public function getDollarRate(): float
    {
        $URL = config('services.flutterwave.url') . "/transfers/rates?amount=1&destination_currency=NGN&source_currency=USD";
        $rate = 8;
        try {
            $responseBody = Http::withToken(config('services.flutterwave.secret'))
                ->get($URL)->json();
            $rate = $responseBody["data"]["rate"];
        } catch (Exception $e) {
            Log::debug("FlutterwaveGateway getDollarRate Error", ['message' => $e->getMessage()]);
        }
        return $rate;
    }

    public function convertAmount($amount, string $currency, string $conversation_rate): string
    {
        if ($currency == "USD") {
            return bcmul($amount, $conversation_rate, 2);
        }

        return (string) ($amount);
    }

    public static function instance(): FlutterwaveGateway
    {
        return new FlutterwaveGateway();
    }
    // getTotal(price) {
    //     if (this.getCurrency() === "USD") {
    //         return `NGN ${price} (est $ ${(
    //             Number(price) * Number(this.$page.props.conversion_rate)
    //         ).toFixed(3)})`;
    //     }
    //     return `NGN ${price}`;
    // },
    public function convertTotal($amount, string $currency, string $conversation_rate): string
    {
        if ($currency == "USD") {
            return "NGN " . $amount . " (est $ " . number_format(bcmul($amount, $conversation_rate, 2), 2) . ")";
        }

        return "NGN " . $amount;
    }

    public function getSubscriptionId(string $email, string $planId): int
    {
        $URL = config('services.flutterwave.url') . "/subscriptions?email=" . $email . "&plan=" . $planId . "&status=active";
        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->get($URL)->json();
        $subscription = $responseBody["data"][0];
        return $subscription["id"];
    }

    public function getSubscriptionIds(string $email, string $planId): array
    {
        $URL = config('services.flutterwave.url') . "/subscriptions?email=" . $email . "&plan=" . $planId . "&status=active";
        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->get($URL)->json();
        $subscription = $responseBody["data"];
        return collect($subscription)->map(function ($sub) {
            return $sub["id"];
        })->toArray();
    }

    public function cancelAllSubscriptions(string $email, string $planId): bool
    {
        $ids = $this->getSubscriptionIds($email, $planId);
        foreach ($ids as $id) {
            $this->cancelSubscription($id);
        }
        return true;
    }

    public function cancelSubscription($id)
    {
        $URL = config('services.flutterwave.url') . "/subscriptions/" . $id . "/cancel";
        $response = Http::withToken(config('services.flutterwave.secret'))
            ->put($URL);
        // confirm 200
        return $response->status() == 200;
    }

    static function getBanks()
    {
        return Cache::remember('flutterwave_banks', 60 * 60 * 24, function () {
            return self::getBanksImpl();
        });
    }

    static function getBanksImpl()
    {

        // NG, GH, KE, UG, ZA or TZ.
        $countries = ["NG", "GH", "KE", "UG", "ZA", "TZ"];
        $result = [];

        foreach ($countries as $country) {
            $URL = config('services.flutterwave.url') . "/banks/" . $country;
            $responseBody = Http::withToken(config('services.flutterwave.secret'))
                ->get($URL)->json();
            $banks = $responseBody["data"];
            // sort banks by name
            usort($banks, function ($a, $b) {
                return $a["name"] <=> $b["name"];
            });
            $result[$country] = $banks;
        }

        return $result;
    }

    static function resolveAccount(GoodMonnifyAccount $account)
    {
        $URL = config('services.flutterwave.url') . "/accounts/resolve";
        $responseBody = Http::withToken(config('services.flutterwave.secret'))
            ->post($URL, [
                "account_number" => $account->getAccountNumber(),
                "account_bank" => $account->getBankCode(),
                "type" => "ACCOUNT",
                "country" => "NG"
            ])->json();
        return $responseBody;

    }
}
