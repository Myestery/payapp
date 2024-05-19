<?php


namespace App\Payments;


enum PaymentActions: string
{
    case CREATE_VIRTUAL_ACCOUNT = 'CREATE_VIRTUAL_ACCOUNT';
    case GET_CARD_PAYMENT_LINK = 'GET_CARD_PAYMENT_LINK';
    case GET_BANKS = 'GET_BANKS';
    case RESOLVE_BANK_ACCOUNT = 'RESOLVE_BANK_ACCOUNT';
    // withdrawals
    case CREATE_WITHDRAWAL = 'CREATE_WITHDRAWAL';
}
