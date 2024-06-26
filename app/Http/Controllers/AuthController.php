<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OTPMail;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {

        $user = User::create($request->all());

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respondWithData(
            data: [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            message: 'Registration successful',
            statusCode: 201
        );
    }

    public function login(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->respondWithData(
            data: [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            message: 'Login successful'
        );
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        // $user->currentAccessToken()->delete();

        $user->tokens()->delete();
        Auth::logout();
        // clear all sessions and cookes
        Session::flush();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    public function otp(Request $request): \Illuminate\Http\JsonResponse
    {
        $otp = rand(1000, 9999);
        // save otp in cache
        cache([$request->user()->id . ':otp' => $otp], now()->addMinutes(5));
        Mail::to($request->user())->send(new OTPMail($otp));
        return $this->respondWithData([], 'OTP sent to your email');
    }
}
