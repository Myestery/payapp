<?php


namespace App\Payments;

use App\Models\Shop;

class PaymentGatewaySwitch
{
    public function __construct(private PaymentGatewayProvider $paymentGatewayProvider)
    {
    }

    /**
     * @throws \Exception
     */
    public function get(Shop $shop, bool $internationalPayment = false): PaymentGateway
    {
        // $countryCodeMap = [
        //     'NGN' => 'NG',
        //     'GHS' => 'GH',
        //     'KES' => 'KE',
        //     'UGX' => 'UG',
        //     'ZAR' => 'ZA',
        //     'TZS' => 'TZ',
        //     'NG' => 'NG',
        //     'GH' => 'GH',
        //     'KE' => 'KE',
        //     'UG' => 'UG',
        //     'ZA' => 'ZA',
        //     'TZ' => 'TZ',
        // ];

        // $countryCode = strtolower($countryCodeMap[$shop->currency]);

        // $scope = $internationalPayment ? 'international' : 'local';

        // $paymentGatewayId = config('payment.' . $countryCode . '.' . $scope, FlutterwaveGateway::ID);

        // return $this->paymentGatewayProvider->get($paymentGatewayId);
        return $this->paymentGatewayProvider->get(FlutterwaveGateway::ID);
    }

    public function getMonnify(): PaymentGateway
    {
        return $this->paymentGatewayProvider->get(MonnifyGateway::ID);
    }

    public function getFlutterwave(): PaymentGateway
    {
        return $this->paymentGatewayProvider->get(FlutterwaveGateway::ID);
    }

}
