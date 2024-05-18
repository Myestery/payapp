<?php


namespace App\Payments;


enum PaymentActions: string
{
    case CREATE_VIRTUAL_ACCOUNT = 'CREATE_VIRTUAL_ACCOUNT';
}
