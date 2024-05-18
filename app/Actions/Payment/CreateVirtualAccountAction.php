<?php


namespace App\Actions\Payment;

use App\Models\Shop;
use App\Models\User;
use App\Payments\PaymentActions;
use Illuminate\Support\Facades\Log;
use App\Payments\PaymentGatewaySwitch;
use App\Payments\VirtualAccountCreationResult;

class CreateVirtualAccountAction
{
    /**
     * CreateSubaccountAction constructor.
     * @param PaymentGatewaySwitch $paymentGatewaySwitch
     */
    public function __construct(
        private PaymentGatewaySwitch $paymentGatewaySwitch
    ) {
    }

    public function execute(User $user): VirtualAccountCreationResult
    {
        $paymentGateway = $this->paymentGatewaySwitch->get(PaymentActions::CREATE_VIRTUAL_ACCOUNT);
        Log::info('[CreateVirtualAccountAction]', ['user' => $user->email, 'payment_processor' => $paymentGateway->getId()]);

        $result = $paymentGateway->createVirtualAccount(
            user: $user,
        );

        return $result;
    }
}
