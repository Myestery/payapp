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
        $logFileName = 'flutterwave-webhook-'.date("Y-m-d-H-i-s").'.log';
        Storage::disk('local')->put($logFileName, json_encode($request->all()));
        // make sure its from flutterwave
        return $next($request);
    }
}
