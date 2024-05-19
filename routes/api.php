<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\PaystackWebhook;
use App\Http\Middleware\SanctumLoggedIn;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\FlutterwaveWebhook;


// AUTH ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');


Route::group(['middleware' => SanctumLoggedIn::class], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        Log::info('User requested', ['user' => auth("sanctum")->user()]);
        return auth("sanctum")->user();
    });

    // ACCOUNT ROUTES
    Route::post('/account', [AccountController::class, 'create']);
    Route::get('/account', [AccountController::class, 'index']);
    Route::post('/account/deposit', [AccountController::class, 'deposit']);
    Route::post('/account/withdraw', [AccountController::class, 'withdraw']);

    // BANK ACCOUNT ROUTES
    Route::get('/bank', [AccountController::class, 'getBanks']);
    Route::post('/bank/resolve', [AccountController::class, 'resolveBankAccount']);
});

// WEBHOOK ROUTES
Route::post('/webhooks/paystack', [WebhookController::class, 'paystackWebhook'])->middleware(PaystackWebhook::class);
Route::post('/webhooks/flutterwave', [WebhookController::class, 'flutterwaveWebhook'])->middleware(FlutterwaveWebhook::class);

// 404
Route::fallback(function () {
    return response()->json(['message' => 'Not Found'], 404);
});
