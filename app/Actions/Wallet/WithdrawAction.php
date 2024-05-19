<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Account;
use App\Models\Withdrawal;
use App\Wallet\WalletConst;
use Illuminate\Support\Str;
use App\Wallet\WalletService;
use App\Payments\PaymentActions;
use Illuminate\Support\Facades\DB;
use App\Payments\PaymentGatewaySwitch;
use App\Exceptions\LowBalanceException;


class WithdrawAction
{
    /**
     * CreateSubaccountAction constructor.
     * @param PaymentGatewaySwitch $paymentGatewaySwitch
     */
    public function __construct(
        private Account $account,
        private $bank_code,
        private $account_number,
        private $amount,
    ) {
    }

    public static function fromRequest(Account $account, $request)
    {
        return new self(
            account: $account,
            amount: $request->amount,
            bank_code: $request->bank_code,
            account_number: $request->account_number,
        );
    }

    public function execute()
    {

        /** @var \App\Payments\PaymentGatewaySwitch  */
        $switch = app()->make(\App\Payments\PaymentGatewaySwitch::class);
        //    make sure the account exists
        $wdlProvider = $switch->get(PaymentActions::CREATE_WITHDRAWAL);
        $resolver = $switch->get(PaymentActions::RESOLVE_BANK_ACCOUNT);
        try {
            $bank = $resolver->resolveBankAccount($this->account_number, $this->bank_code);
        } catch (\Throwable $th) {
            return $this->respondWithError("Could not resolve Account details", 400);
        }
        $ref = Str::uuid();
        $account = $this->account;
        $amount = $this->amount;


        $exception = DB::transaction(function () use ($account, $amount, $wdlProvider, $bank, $ref) {

            // create a ledger and apply debit
            /** @var \App\Wallet\WalletService */
            $walletService = app()->make(\App\Wallet\WalletService::class);

            $gl = $wdlProvider->getGL();
            // $txReference = $transferResponse["data"]["nip_transaction_reference"];

            $ledgers = [
                new \App\Wallet\Ledger(
                    action: WalletConst::DEBIT,
                    account_id: $account->id,
                    amount: $amount,
                    narration: "PAYOUT/" . $ref,
                    category: "PAYOUT",
                ),
                new \App\Wallet\Ledger(
                    action: WalletConst::CREDIT,
                    account_id: $gl->id,
                    amount: $amount,
                    narration: "PAYOUT/" . $ref,
                    category: "PAYOUT",
                ),
            ];

            $res = $walletService->post(
                reference: $ref,
                total_amount: $amount,
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
                'amount' => $amount,
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
    }
}
