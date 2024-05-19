<?php

namespace App\Exceptions;

use Exception;
use App\Mail\LowBalanceMail;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LowBalanceException extends Exception
{
    use ApiResponse;
     /**
     * Render the exception into an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        // dd($request->all(), $request->user());
        Mail::to($request->user())->send(new LowBalanceMail(
            'Your account balance is low. Please top up to continue using our services'
        ));

        return $this->respondWithError('Low balance', 400);
    }
}
