<?php

namespace App\Wallet;

use App\Dto\GeneralConst;

class WalletResponse
{

    public function __construct(public $status, public $message, public $reference, public $total_amount = 0.00)
    {
    }

    public function isSuccessful(){
        return ($this->status == GeneralConst::SUCCESSFUL) ? true : false;
    }

    public function isFailed(){
        return ($this->status != GeneralConst::SUCCESSFUL) ? true : false;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
