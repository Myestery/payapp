<?php

namespace App\Jobs;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use App\Payments\PaymentActions;
use App\Payments\PaymentGatewaySwitch;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessWithdrawal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Withdrawal $withdrawal)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewaySwitch $paymentGatewaySwitch): void
    {
        $paymentGateway = $paymentGatewaySwitch->get(PaymentActions::CREATE_WITHDRAWAL);
        $result = $paymentGateway->createWithdrawal(
            bankCode: $this->withdrawal->bank_code,
            accountNumber: $this->withdrawal->account_number,
            amount: $this->withdrawal->amount,
            reference: $this->withdrawal->reference
        );
        // update withdrawal status
        $this->withdrawal->update([
            'status' => $result->status,
            'session_id' => $result->providerId,
            'response_message' => $result->message,
        ]);
    }
}
