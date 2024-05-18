<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|in:NGN',
            'email_subscribe' => 'required|boolean',
        ]);
        // idempotent create account cos a user should not have more than 1 account
        $acc =  Account::firstOrCreate([
            'user_id' => $request->user()->id,
            'currency' => $request->currency,
            'email_subscribe' => $request->email_subscribe,
        ]);
        return $this->respondWithData($acc, 'Account created successfully', 201);
    }
}
