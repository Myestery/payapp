<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanctumLoggedIn
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // clear cookies and session

        if (auth("sanctum")->check()) {
            // attach the user to the request
            $request->setUserResolver(function () {
                return auth("sanctum")->user();
            });
            return $next($request);
        }

        throw new \App\Exceptions\NotAuthenticated();
    }
}
