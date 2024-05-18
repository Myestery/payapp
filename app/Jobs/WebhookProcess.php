<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Payments\WebhookResource;
use Illuminate\Queue\SerializesModels;
use App\Payments\PaymentGatewayProvider;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class WebhookProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private WebhookResource $resource,
        private string $gateway
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentGatewayProvider $switch)
    {
        $gateway = $switch->get($this->gateway);
        $gateway->processWebhook($this->resource);
    }
}
