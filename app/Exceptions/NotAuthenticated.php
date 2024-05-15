<?php

namespace App\Exceptions;

use Exception;

class NotAuthenticated extends Exception
{
    // return { status: 'error', message: 'Unauthorized', data: null } with 401
    public function render($request)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
            'data' => null,
        ], 401);
    }

    // do not report
    public function report()
    {
        return false;
    }
}
