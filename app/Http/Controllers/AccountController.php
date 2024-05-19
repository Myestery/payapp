<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Payments\PaymentData;
use App\Payments\PaymentActions;
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

    public function withdraw(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
            'account_number' => 'required|string',
            'bank_code' => 'required|string',
        ]);

        $account = $request->user()->account;
        $provider = PaymentGatewayProvider::getProvider($account->currency);
        $link = $provider->initiatePayment(
            new PaymentData(
                customerName: $account->name,
                currency: "NGN",
                customerEmail: $request->user()->email,
                referenceCode: Str::uuid(),
                redirectUrl: "https://google.com",
                totalAmount: $request->amount,
                customerPhone: $request->user()->phone,
                paymentDescription: 'Withdraw from account',
                method: 'bank-transfer'
            )
        );
        return $this->respondWithData($link, 'Withdrawal initiated');
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
}
