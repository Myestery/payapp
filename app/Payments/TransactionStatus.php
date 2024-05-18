<?php

namespace App\Payments;


enum TransactionStatus: string {
    case PAID = 'paid';
    case PENDING = 'pending';
    case FAILED = 'failed';
}
