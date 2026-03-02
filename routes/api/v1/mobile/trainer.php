<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Mobile\Trainer\AuthController;

Route::prefix('trainer')->group(function () {

        Route::post('send-otp', [AuthController::class, 'sendOtp']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        
        Route::middleware('auth:trainer')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
        });
    });
