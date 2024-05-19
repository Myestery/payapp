<?php


namespace App\Payments;

use DateTime;
use App\Models\User;
use App\Models\Account;
use App\Models\VirtualAccount;
use App\Payments\PaymentMethod;

class TransactionData
{
    public function __construct(
        public float     $amountPaid,
        public float     $settlementAmount,
        public PaymentMethod|string    $paymentMethod,
        public TransactionStatus    $status,
        public ?string   $internalTxId = null,
        public ?string   $externalTxId = null,
        public ?DateTime $paidOn = null,
        public ?string   $customerEmail = null,
        public ?string   $destinationAccount = null,
        public ?float   $fee = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'amountPaid' => $this->amountPaid,
            'settlementAmount' => $this->settlementAmount,
            'paymentMethod' => $this->paymentMethod,
            'status' => $this->status,
            'internalTxId' => $this->internalTxId,
            'externalTxId' => $this->externalTxId,
            'paidOn' => $this->paidOn,
        ];
    }

    public function getAccountFromTx(): Account
    {
        $tx = $this;
        $account = null;
        switch ($tx->paymentMethod) {
            case PaymentMethod::CARD:
                // for card payments, we can use the email to get the account
                $user = User::where('email', $tx->customerEmail)->first();
                if (!$user) {
                    break;
                }
                $account = Account::where('user_id', $user->id)->first();
                break;
            case PaymentMethod::BANK_TRANSFER:
                // for bank transfers,
                $vAccount = \App\Models\VirtualAccount::where('account_number', $tx->destinationAccount)->first();
                $account = $vAccount->account;
                break;
            default:
                $user = User::where('email', $tx->customerEmail)->first();
                if (!$user) {
                    break;
                }
                $account = Account::where('user_id', $user->id)->first();
                break;
        }

        if (!$account) {
            throw new \Exception('Account not found');
        }

        return $account;
    }
}
