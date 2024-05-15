<?php


namespace App\Actions\Payments;

use App\Models\Shop;
use App\Payments\PaymentGatewayProvider;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class CreateVirtualAccountAction
{
    use QueueableAction;

    /**
     * CreateSubaccountAction constructor.
     * @param PaymentGatewayProvider $paymentGatewayProvider
     */
    public function __construct(private PaymentGatewayProvider $paymentGatewayProvider)
    {
    }

    public function execute(Shop $shop, string $paymentProcessorId): string
    {
        Log::info('[CreateSubaccountAction]', ['shop' => $shop->slug, 'payment_processor' => $paymentProcessorId]);

        $paymentGateway = $this->paymentGatewayProvider->get($paymentProcessorId);

        $result = $paymentGateway->createSubAccount(
            bankCode: $shop->bankAccount->bank_code,
            accountNumber: $shop->bankAccount->account_number,
            splitPercentage: 95,
            email: $shop->owner->email,
            accountName: $shop->name,
            currencyCode: $shop->currency
        );

        $shop->setSubaccountCode($paymentGateway->getId(), $result);
        return $result->subAccountCode;
    }

    public function __invoke(Shop $shop, string $paymentProcessorId): string
    {
        return $this->execute($shop, $paymentProcessorId);
    }
}
