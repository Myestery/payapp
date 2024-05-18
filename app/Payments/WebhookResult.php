<?php

namespace App\Payments;

class WebhookResult
{
    public function __construct(
        public $account_id,
        public $amount,
        public $successful,
    ) {
    }

}
