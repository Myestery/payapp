<?php

namespace App\Payments;


class WithdrawalResult
{
    public function __construct(public $status, public $message, public $providerId = null)
    {
    }


    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'providerId' => $this->providerId
        ];
    }
}
