<?php

namespace App\Actions\Wallet;

use App\Models\User;
use App\Models\Account;
use App\Wallet\WalletConst;
use Illuminate\Support\Str;
use App\Wallet\WalletService;
use Illuminate\Support\Facades\DB;
use App\Exceptions\LowBalanceException;
use App\Traits\ApiResponse;

class TransferAction
{
    use ApiResponse;
    /**
     * CreateSubaccountAction constructor.
     * @param PaymentGatewaySwitch $paymentGatewaySwitch
     */
    public function __construct(
        private Account $account,
        private $amount,
        private $email,
    ) {
    }

    public static function fromRequest(Account $account, $request)
    {
        return new self(
            account: $account,
            amount: $request->amount,
            email: $request->email,
        );
    }

    public function execute()
    {
        $feeGL = Account::where('name', "FEES GL")->first();
        $account = $this->account;
        $amount = $this->amount ;
        $email = $this->email;
        if ($account->balance < $amount + WalletConst::TRANSFER_FEE) {
            throw new LowBalanceException("Insufficient balance");
        }
        $recAccount = User::where('email', $email)->first()->account;
        if (!$recAccount) {
            return $this->respondWithError("Recipient account not found", 400);
        }

        $ref = Str::uuid();
        $exception = DB::transaction(function () use ($account, $amount, $ref, $recAccount, $feeGL) {
            // create a ledger and apply debit
            /** @var \App\Wallet\WalletService */
            $walletService = app()->make(\App\Wallet\WalletService::class);

            $ledgers = [
                new \App\Wallet\Ledger(
                    action: WalletConst::DEBIT,
                    account_id: $account->id,
                    amount: $amount + WalletConst::TRANSFER_FEE,
                    narration: "TRANSFER/" . $ref,
                    category: "TRANSFER",
                ),
                new \App\Wallet\Ledger(
                    action: WalletConst::CREDIT,
                    account_id: $recAccount->id,
                    amount: $amount,
                    narration: "TRANSFER/" . $ref,
                    category: "TRANSFER",
                ),
                // ADD LEDGER FOR FEES
                new \App\Wallet\Ledger(
                    action: WalletConst::CREDIT,
                    account_id: $feeGL->id,
                    amount: WalletConst::TRANSFER_FEE,
                    narration: "TRANSFER FEE/" . $ref,
                    category: "TRANSFER FEE",
                ),

            ];

            $res = $walletService->post(
                reference: $ref,
                total_amount: $amount + WalletConst::TRANSFER_FEE,
                ledgers: $ledgers,
                provider: "internal",
            );

            if (!$res->isSuccessful()) {
                throw new \Exception("An error occurred while processing your request, please try again later");
            }

        });

        if ($exception) {
            throw new \Exception(
                "An error occurred while processing your request, please try again later"
            );
        }
    }
}
