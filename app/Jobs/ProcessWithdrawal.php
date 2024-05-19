<?php

namespace App\Jobs;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use App\Mail\TransferFailedMail;
use App\Payments\PaymentActions;
use App\Mail\TransferSuccessMail;
use Illuminate\Support\Facades\Mail;
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
        try {
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
            // mail user of successful transfer
            Mail::to($this->withdrawal->account->user)->send(new TransferSuccessMail("Your withdrawal request was successful. Reference: {$this->withdrawal->reference}"));
        } catch (\Throwable $th) {
            // mail user of failed transfer
            Mail::to($this->withdrawal->account->user)->send(new TransferFailedMail('Your withdrawal request failed: ' . $th->getMessage()));
            throw $th;
        }
    }
}
