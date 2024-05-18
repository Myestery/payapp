<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PaystackWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // log the request
        $logFileName = 'paystack-webhook-'.date("Y-m-d-H-i-s").'.log';
        Storage::disk('local')->put($logFileName, json_encode($request->all()));
        // Verify the webhook signature
        $paystackSignature = $request->header('x-paystack-signature');
        $secretKey = config("paystack.secretKey");

        if (!$paystackSignature || !$this->verifySignature($request->getContent(), $paystackSignature, $secretKey)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }
        // verify IP address

        $allowedIps = config('paystack.allowedIps');

        if (!in_array($request->ip(), $allowedIps) && !in_array('*', $allowedIps)) {
            return response()->json(['message' => 'Invalid IP address'], 403);
        }

        return $next($request);
    }


    private function verifySignature($payload, $paystackSignature, $secretKey)
    {
    //   try to json_encode and decode the payload cos of whitespace
        $payload = json_encode(json_decode($payload));
        return hash_hmac('sha512', $payload, $secretKey) === $paystackSignature;
    }
}
