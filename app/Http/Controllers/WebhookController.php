<?php

namespace App\Http\Controllers;

use App\Payments\FlutterwaveGateway;
use App\Payments\PaymentGatewayProvider;
use App\Payments\PaystackGateway;
use App\Payments\WebhookResource;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function paystackWebhook(Request $request, PaystackGateway $paystack)
    {
        $data = $request->all();
        $resource = WebhookResource::fromArray($data);
        $resource->process($paystack->getId());

        return $this->respondWithData([], 'Webhook received successfully', 200);
    }

    public function flutterwaveWebhook(Request $request, FlutterwaveGateway $flutterwaveGateway)
    {
        $data = $request->all();
        $resource = WebhookResource::fromArray($data);
        $resource->process($flutterwaveGateway->getId());

        return $this->respondWithData([], 'Webhook received successfully', 200);
    }
}
