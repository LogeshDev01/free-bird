<?php

namespace App\Http\Controllers\Api\v1\Mobile\Trainer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Trainer;
use App\Models\RefreshToken;
use App\Models\OtpVerification;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 1️⃣ SEND OTP
    |--------------------------------------------------------------------------
    */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required'
        ]);

        $trainer = Trainer::where('phone', $request->mobile)->first();

        if (!$trainer) {
            return response()->json([
                'error' => 'Trainer not found'
            ], 404);
        }

        // $otp = rand(1000, 9999);
        $otp = "1234";

        OtpVerification::create([
            'mobile_number' => $request->mobile,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5),
            'device' => $request->userAgent()
        ]);

        // TODO: Integrate SMS provider here

        return response()->json([
            'message' => 'OTP sent successfully',
            // 'otp' => $otp
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 2️⃣ VERIFY OTP + LOGIN
    |--------------------------------------------------------------------------
    */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required',
            'otp'    => 'required'
        ]);

        $otpRecord = OtpVerification::where([
                'mobile_number' => $request->mobile,
                'otp' => $request->otp,
                'is_verified' => false
            ])
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'error' => 'Invalid or expired OTP'
            ], 401);
        }

        $trainer = Trainer::where('phone', $request->mobile)->first();

        if (!$trainer) {
            return response()->json([
                'error' => 'Trainer not found'
            ], 404);
        }

        $otpRecord->update(['is_verified' => true]);

        // Generate access token
        $accessToken = auth()->guard('trainer')->login($trainer);

        // Generate refresh token
        $refreshToken = Str::random(64);

        RefreshToken::create([
            'tokenable_id'   => $trainer->id,
            'tokenable_type' => Trainer::class,
            'token'          => hash('sha256', $refreshToken),
            'expires_at'     => now()->addDays(30),
            'device'         => $request->userAgent()
        ]);

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => auth()->guard('trainer')->factory()->getTTL() * 60,
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => 30 * 24 * 60 * 60,
            'trainer' => $trainer
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3️⃣ REFRESH TOKEN
    |--------------------------------------------------------------------------
    */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        $hashed = hash('sha256', $request->refresh_token);

        $tokenRecord = RefreshToken::where('token', $hashed)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        }

        $trainer = $tokenRecord->tokenable;

        $tokenRecord->delete(); // rotate

        $newRefresh = Str::random(64);

        RefreshToken::create([
            'tokenable_id'   => $trainer->id,
            'tokenable_type' => Trainer::class,
            'token'          => hash('sha256', $newRefresh),
            'expires_at'     => now()->addDays(30),
            'device'         => $request->userAgent()
        ]);

        $newAccess = auth()->guard('trainer')->login($trainer);

        return response()->json([
            'message'            => 'Token refreshed',
            'access_token'       => $newAccess,
            'token_type'         => 'Bearer',
            'expires_in'         => auth()->guard('trainer')->factory()->getTTL() * 60,
            'refresh_token'      => $newRefresh,
            'refresh_expires_in' => 30 * 24 * 60 * 60,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 4️⃣ LOGOUT
    |--------------------------------------------------------------------------
    */
    public function logout(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        RefreshToken::where(
            'token',
            hash('sha256', $request->refresh_token)
        )->delete();

        auth()->guard('trainer')->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}