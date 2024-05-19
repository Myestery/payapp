<?php

namespace App\Wallet;

interface WalletConst
{
    const DEBIT = 'DEBIT';
    const CREDIT = 'CREDIT';

    // WALLET STATUS
    const PENDING = 1;
    const SUCCESSFUL = 2;
    const FAILED = 0;

    const GL_ACCOUNT = 'GL';

    const TRANSFER_FEE = 5;
}
