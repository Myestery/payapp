<?php

namespace App\Http\Controllers;

use App\Exceptions\LowBalanceException;
use App\Exceptions\TransferFailedException;
use App\Models\User;
use App\Models\Account;
use App\Wallet\WalletConst;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Payments\PaymentData;
use App\Payments\PaymentActions;
use Illuminate\Support\Facades\DB;
use App\Payments\PaymentGatewaySwitch;
use App\Payments\PaymentGatewayProvider;

class AccountController extends Controller
{
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|in:NGN',
            'email_subscribe' => 'required|boolean',
        ]);
        // idempotent create account cos a user should not have more than 1 account
        $acc =  Account::firstOrCreate([
            'user_id' => $request->user()->id,
            'currency' => $request->currency,
            'email_subscribe' => $request->email_subscribe,
            'name' => $request->user()->name
        ]);
        return $this->respondWithData($acc, 'Account created successfully', 201);
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $account = $request->user()->account;
        return $this->respondWithData($account, 'Account retrieved successfully');
    }

    public function deposit(Request $request, PaymentGatewaySwitch $switch): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'channel' => 'required|string|in:bank-transfer,card',
            'amount' => 'required|numeric|min:1000',
        ]);

        $account = $request->user()->account;
        switch ($request->channel) {
            case 'bank-transfer':
                return $this->respondWithData($account->virtualAccount, 'Deposit via bank transfer');
            case 'card':
                $provider = $switch->get(PaymentActions::GET_CARD_PAYMENT_LINK);
                $link = $provider->initiatePayment(
                    new PaymentData(
                        customerName: $account->name,
                        currency: "NGN",
                        customerEmail: $request->user()->email,
                        referenceCode: Str::uuid(),
                        redirectUrl: "https://google.com",
                        totalAmount: $request->amount,
                        customerPhone: $request->user()->phone,
                        paymentDescription: 'Deposit to account',
                        method: 'card'
                    )
                );
                return $this->respondWithData($link, 'Deposit via card');
            default:
                return $this->respondWithError('Invalid channel', 400);
        }
    }

    public function withdraw(Request $request, PaymentGatewaySwitch $switch): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $account = $request->user()->account;
        if ($account->balance < $request->amount) {
            throw new LowBalanceException("Insufficient balance");
        }
        //    make sure the account exists
        $wdlProvider = $switch->get(PaymentActions::CREATE_WITHDRAWAL);
        $resolver = $switch->get(PaymentActions::RESOLVE_BANK_ACCOUNT);
        try {
            $bank = $resolver->resolveBankAccount($request->account_number, $request->bank_code);
        } catch (\Throwable $th) {
            return $this->respondWithError("Could not resolve Account details", 400);
        }
        $ref = Str::uuid();


        $exception = DB::transaction(function () use ($account, $request, $wdlProvider, $bank, $ref) {

            // create a ledger and apply debit
            /** @var \App\Wallet\WalletService */
            $walletService = app()->make(\App\Wallet\WalletService::class);

            $gl = $wdlProvider->getGL();
            // $txReference = $transferResponse["data"]["nip_transaction_reference"];

            $ledgers = [
                new \App\Wallet\Ledger(
                    action: WalletConst::DEBIT,
                    account_id: $account->id,
                    amount: $request->amount,
                    narration: "PAYOUT/" . $ref,
                    category: "PAYOUT",
                ),
                new \App\Wallet\Ledger(
                    action: WalletConst::CREDIT,
                    account_id: $gl->id,
                    amount: $request->amount,
                    narration: "PAYOUT/" . $ref,
                    category: "PAYOUT",
                ),
            ];

            $res = $walletService->post(
                reference: $ref,
                total_amount: $request->amount,
                ledgers: $ledgers,
                provider: $wdlProvider->getId(),
            );

            if (!$res->isSuccessful()) {
                throw new \Exception("An error occurred while processing your request, please try again later");
            }

            \App\Models\Withdrawal::create([
                'account_id' => $account->id,
                'bank_name' => $bank->bankName,
                'account_name' => $bank->accountName,
                'account_number' => $bank->accountNumber,
                'bank_code' => $bank->bankCode,
                'amount' => $request->amount,
                'provider' => $wdlProvider->getId(),
                'status' => "pending",
                'reference' => "PAYOUT/" . $ref,
                'session_id' => null,
                'wallet_debited' => true,
                'value_given' => false,
                'response_code' => null,
                'response_message' => null,
            ]);
        });

        if ($exception) {
            throw new \Exception(
                "An error occurred while processing your request, please try again later"
            );
        }

        return $this->respondWithData([], 'Withdrawal request successful');
    }

    public function getBanks(PaymentGatewaySwitch $switch): \Illuminate\Http\JsonResponse
    {
        $provider = $switch->get(PaymentActions::GET_BANKS);
        $banks = $provider->getBanks();
        return $this->respondWithData($banks, 'Banks retrieved successfully');
    }

    public function resolveBankAccount(Request $request, PaymentGatewaySwitch $switch): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $provider = $switch->get(PaymentActions::RESOLVE_BANK_ACCOUNT);
        try {
            $bank = $provider->resolveBankAccount($request->account_number, $request->bank_code);
        } catch (\Throwable $th) {
            return $this->respondWithError("Could not resolve Account details", 400);
        }
        return $this->respondWithData($bank, 'Bank resolved successfully');
    }

    public function transfer(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'email' => 'required|exists:users,email',
        ]);

        $account = $request->user()->account;
        if ($account->balance < $request->amount) {
            throw new LowBalanceException("Insufficient balance");
        }
        $recAccount = User::where('email', $request->email)->first()->account;
        if (!$recAccount) {
            return $this->respondWithError("Recipient account not found", 400);
        }

        $ref = Str::uuid();
        $exception = DB::transaction(function () use ($account, $request, $ref, $recAccount) {
            // create a ledger and apply debit
            /** @var \App\Wallet\WalletService */
            $walletService = app()->make(\App\Wallet\WalletService::class);

            $ledgers = [
                new \App\Wallet\Ledger(
                    action: WalletConst::DEBIT,
                    account_id: $account->id,
                    amount: $request->amount,
                    narration: "TRANSFER/" . $ref,
                    category: "TRANSFER",
                ),
                new \App\Wallet\Ledger(
                    action: WalletConst::CREDIT,
                    account_id: $recAccount->id,
                    amount: $request->amount,
                    narration: "TRANSFER/" . $ref,
                    category: "TRANSFER",
                ),
            ];

            $res = $walletService->post(
                reference: $ref,
                total_amount: $request->amount,
                ledgers: $ledgers,
                provider: "internal",
            );

            if (!$res->isSuccessful()) {
                throw new \Exception("An error occurred while processing your request, please try again later");
            }

            if (!$res->isSuccessful()) {
                throw new \Exception("An error occurred while processing your request, please try again later");
            }
        });

        if ($exception) {
            throw new \Exception(
                "An error occurred while processing your request, please try again later"
            );
        }

        return $this->respondWithData([], 'Transfer successful');
    }

    public function history(Request $request): \Illuminate\Http\JsonResponse
    {
        $account = $request->user()->account;
        $history = $account->histories;
        return $this->respondWithData($history, 'Transaction history retrieved successfully');
    }
}
