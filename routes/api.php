<?php

use Illuminate\Http\Request;
use App\Http\Middleware\HasAccount;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\RequiresOTP;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\PaystackWebhook;
use App\Http\Middleware\SanctumLoggedIn;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\WebhookController;
use App\Http\Middleware\FlutterwaveWebhook;
use App\Http\Middleware\TransactionLimitChecker;

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
    Route::post('/account/deposit', [AccountController::class, 'deposit'])
        ->middleware(HasAccount::class);
    Route::post('/account/withdraw', [AccountController::class, 'withdraw'])
        ->middleware(HasAccount::class)
        ->middleware(TransactionLimitChecker::class)
        ->middleware(RequiresOTP::class);
    Route::post('/account/transfer', [AccountController::class, 'transfer'])
        ->middleware(HasAccount::class)
        ->middleware(TransactionLimitChecker::class);
        // ->middleware(RequiresOTP::class);
    Route::get('/account/history', [AccountController::class, 'history'])->middleware(HasAccount::class);

    Route::get('otp', [AuthController::class, 'otp']);

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
