<?php


namespace App\Payments;


use Exception;

class PaymentGatewayProvider
{
    /**
     * @throws Exception
     */
    public function get(string $id): PaymentGateway
    {
        return match ($id){
            MonnifyGateway::ID => app(MonnifyGateway::class),
            FlutterwaveGateway::ID => app(FlutterwaveGateway::class),
            PaystackGateway::ID => app(PaystackGateway::class),
            default => throw new Exception($id.' payment gateway not supported.')
        };
    }
}
