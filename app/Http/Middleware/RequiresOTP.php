<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RequiresOTP
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // there must be otp in the body of the request and it must match Cache::get('$user->id':otp)

        if (!$request->has('otp')) {
            return response()->json(['message' => 'OTP is required'], 400);
        }

        $otp =  (int)$request->otp;

        if ($otp !== Cache::get($request->user()->id . ':otp')) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        // delete the otp from cache
        Cache::forget($request->user()->id . ':otp');

        return $next($request);
    }
}
