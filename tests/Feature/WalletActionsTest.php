<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Account;
use App\Wallet\WalletConst;
use Illuminate\Support\Facades\Queue;
use App\Actions\Wallet\TransferAction;
use App\Actions\Wallet\WithdrawAction;
use App\Exceptions\LowBalanceException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletActionsTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_that_fee_is_charged_on_all_transfers()
    {
        Queue::fake();
        $account = Account::factory()->create([
            'balance' => 1000
        ]);
        // ensure first user has an account too
        Account::where(["user_id" => 1])->delete();
        Account::factory()->create([
            'balance' => 1000,
            'user_id' => 1
        ]);


        TransferAction::fromRequest(
            account: $account,
            request: (object) ['amount' => 100, 'email' => User::first()->email]
        )->execute();

        $this->assertEquals(900 - WalletConst::TRANSFER_FEE, $account->fresh()->balance);
    }

    public function test_that_transfer_is_rejected_if_balance_is_low()
    {
        Queue::fake();
        $account = Account::factory()->create([
            'balance' => 1000
        ]);
        $this->expectExceptionMessage('Insufficient balance');
        TransferAction::fromRequest(
            account: $account,
            request: (object) ['amount' => 1001, 'email' => User::first()->email]
        )->execute();
    }

    public function test_that_transfer_is_rejected_if_recipient_account_is_not_found()
    {
        Queue::fake();
        $account = Account::factory()->create([
            'balance' => 1000
        ]);
        $this->expectException(\Exception::class);
        TransferAction::fromRequest(
            account: $account,
            request: (object) ['amount' => 100, 'email' => 'a@gmail.com']
        )->execute();

    }

    public function test_that_withdrawal_fails_if_balance_is_low()
    {
        Queue::fake();
        $account = Account::factory()->create([
            'balance' => 1000
        ]);
        $this->expectException(LowBalanceException::class);
        WithdrawAction::fromRequest(
            account: $account,
            request: (object) ['amount' => 1001, 'bank_code' => '011', 'account_number' => '1234567890']
        )->execute();
    }

}
