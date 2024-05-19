<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionLimitChecker
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // load the tx limit of the user's account
        $txLimit = $request->user()->account->transaction_limit;
        // check if all transactions are today are within the limit, + the amount of the current transaction
        $totalTxAmount = $request->user()->account->histories
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('amount');
        $totalTxAmount += $request->amount;

        if ($totalTxAmount > $txLimit) {
            return $this->respondWithError('Transaction limit exceeded', 400);
        }
        return $next($request);
    }
}
