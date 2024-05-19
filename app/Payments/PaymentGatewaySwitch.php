<?php


namespace App\Payments;


class PaymentGatewaySwitch
{
    public function __construct(private PaymentGatewayProvider $paymentGatewayProvider)
    {
    }

    /**
     * @throws \Exception
     */
    public function get(PaymentActions $action): PaymentGateway
    {
        return match($action) {
            PaymentActions::CREATE_VIRTUAL_ACCOUNT => $this->getPaystack(),
            PaymentActions::GET_CARD_PAYMENT_LINK => $this->getFlutterwave(),
            PaymentActions::GET_BANKS => $this->getPaystack(),
            PaymentActions::RESOLVE_BANK_ACCOUNT => $this->getPaystack(),
            PaymentActions::CREATE_WITHDRAWAL => $this->getFlutterwave(),
            default => throw new \Exception($action . ' action not supported.')
        };
    }

    public function getFlutterwave(): PaymentGateway
    {
        return $this->paymentGatewayProvider->get(FlutterwaveGateway::ID);
    }

    public function getPaystack(): PaymentGateway
    {
        return $this->paymentGatewayProvider->get(PaystackGateway::ID);
    }

}
