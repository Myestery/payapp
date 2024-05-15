<?php


namespace App\Payments;

use DateTime;

class CardTransactionData
{
    public function __construct(
        public string     $first_6digits,
        public string     $last_4digits,
        public string    $issuer,
        public string    $country,
        public string    $type,
        public string    $token,
        public string    $expiry,
        public string   $processor_response,
    ) {
    }
}