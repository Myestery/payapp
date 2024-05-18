<?php


namespace App\Payments;


enum WalletConst: string
{
    case GL = 'GL';
    case REGULAR = 'REGULAR';

    const DEBIT = 'DEBIT';
    const CREDIT = 'CREDIT';

    // WALLET STATUS
    const PENDING = 1;
    const SUCCESSFUL = 2;
    const FAILED = 0;
}
