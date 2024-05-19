<?php


namespace App\Payments;


enum PaymentActions: string
{
    case CREATE_VIRTUAL_ACCOUNT = 'CREATE_VIRTUAL_ACCOUNT';
    case GET_CARD_PAYMENT_LINK = 'GET_CARD_PAYMENT_LINK';
}
