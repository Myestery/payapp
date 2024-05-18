<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FlutterwaveWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // save the webhook payload to a log file, date and seconds
        $logFileName = 'flutterwave-webhook-'.date("Y-m-d-H-i-s").uniqid().'.log';
        Storage::disk('local')->put($logFileName, json_encode($request->all()));
        // make sure its from flutterwave
        if (!$this->verifyHash($request)) {
            return response()->json(['message' => 'Invalid hash'], 401);
        }
        // check for IP
        $this->validateIP($request);
        return $next($request);
    }

    private function verifyHash(Request $request): bool
    {
        return $request->header('verif-hash') === config("services.flutterwave.verif-hash");
    }

    private function validateIP($request)
    {
        $allowedIps = config('services.flutterwave.allowedIps');

        if (!in_array($request->ip(), $allowedIps) && !in_array('*', $allowedIps)) {
            return response()->json(['message' => 'Invalid IP address'], 403);
        }
    }
}
