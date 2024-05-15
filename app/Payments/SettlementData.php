<?php


namespace App\Payments;

use Carbon\Carbon;

class SettlementData
{
    public function __construct(
        public string $settlementStatus,
        public string $beneficiaryType,
        public ?float $amount,
        public ?Carbon $settlementDate,
        public ?string $settlementReference,
    )
    {
    }

}
