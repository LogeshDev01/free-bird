<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Mobile\WaterLogController;

Route::prefix('client')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 🔒 PROTECTED (Auth Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:client')->group(function () {

        // ── Water Logs ──────────────────────────────────
        Route::get('water-logs', [WaterLogController::class, 'index']);
        Route::get('water-logs/weekly', [WaterLogController::class, 'weekly']);
        Route::post('water-logs', [WaterLogController::class, 'store']);
        Route::put('water-logs/goal', [WaterLogController::class, 'updateGoal']);
        Route::delete('water-logs/{id}', [WaterLogController::class, 'destroy']);

    });
});
