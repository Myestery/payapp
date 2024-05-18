<?php

namespace App\Wallet;

use App\Models\Account;
class Ledger implements \JsonSerializable
{
    public $account;
    public function __construct(public $action, public $account_id, public $amount, public $narration, public $category)
    {
        $this->amount = round($amount, 2);
        $this->account = Account::find($this->account_id);
    }

    public function jsonSerialize()
    {
        return [
            'action' => $this->action,
            'amount' => $this->amount,
            'narration' => $this->narration,
            'category' => $this->category,
            'account_id' => $this->account_id
        ];
    }
}
