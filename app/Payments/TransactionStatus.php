<?php

namespace App\Payments;

use Spatie\Enum\Laravel\Enum;

/**
 * @method static self PAID()
 * @method static self PENDING()
 * @method static self FAILED()
 */
enum TransactionStatus: string {
    case PAID = 'paid';
    case PENDING = 'pending';
    case FAILED = 'failed';
}
