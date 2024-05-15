<?php

namespace App\Payments;

enum PaymentMethod: string {
    case CARD = 'card';
    case BANK_TRANSFER = 'bank_transfer';
}
