<?php

namespace Tests;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use Mockery;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
// use PHPUnit\Framework\TestCase;
use Mockery\MockInterface;
use App\Payments\PaymentActions;
use App\Payments\PaystackGateway;
use App\Jobs\CreateVirtualAccount;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Payments\PaymentGatewaySwitch;
use App\Payments\PaymentGatewayProvider;
use Illuminate\Foundation\Testing\WithFaker;

// use Illuminate\Foundation\Testing\WithFaker;

class VirtualAccountTest extends TestCase
{
    use WithFaker;

    public function testVirtualAccountJobIsFiredAfterAccountIsCreated()
    {
        Queue::fake();
        Account::factory()->create();
        Queue::assertPushed(CreateVirtualAccount::class);
    }

    public function testVirtualAccountJobIsHandledByPaystackGateway()
    {
        $this->instance(PaymentGatewayProvider::class, Mockery::mock(PaymentGatewayProvider::class, function (MockInterface $mock) {
            $switch = new PaymentGatewaySwitch($mock);
            $mock->shouldReceive('get')->with(PaystackGateway::ID)->once()->andReturn(new PaystackGateway);
            $switch->get(PaymentActions::CREATE_VIRTUAL_ACCOUNT);
        }));
    }
}
