<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Withdrawal;
use App\Jobs\ProcessWithdrawal;
use App\Payments\PaymentActions;
use Illuminate\Support\Facades\Queue;
use App\Payments\PaymentGatewaySwitch;
use App\Payments\PaymentGatewayProvider;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentSwitchTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_that_all_card_initiate_requests_go_through_flutterwave(): void
    {
        $paymentGatewayProvider = $this->instance(PaymentGatewayProvider::class, new PaymentGatewayProvider());
        $switch = $this->instance(PaymentGatewaySwitch::class, new PaymentGatewaySwitch($paymentGatewayProvider));

        $this->assertEquals($switch->get(PaymentActions::GET_CARD_PAYMENT_LINK), $switch->getFlutterwave());

    }

    public function testTransferIsQueuedAfterWithdrawalIsMade()
    {
        Queue::fake();
        Withdrawal::factory()->create();
        Queue::assertPushed(ProcessWithdrawal::class);
    }
}
