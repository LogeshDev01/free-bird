<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Mobile\WaterLogController;
use App\Http\Controllers\Api\v1\Mobile\CommunityController;
use App\Http\Controllers\Api\v1\Mobile\SupportController;

Route::prefix('client')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 🔓 PUBLIC (No Auth Required)
    |--------------------------------------------------------------------------
    */
    Route::get('faqs', [SupportController::class, 'getFaqs']);
    Route::get('pages/{key}', [SupportController::class, 'getPage']);

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

        // ── Community ──────────────────────────────────
        Route::get('community/categories', [CommunityController::class, 'categories']);
        Route::get('community/posts', [CommunityController::class, 'index']);
        Route::post('community/posts', [CommunityController::class, 'store']);
        Route::post('community/posts/{id}/like', [CommunityController::class, 'toggleLike']);
        Route::get('community/posts/{id}/comments', [CommunityController::class, 'getComments']);
        Route::post('community/posts/{id}/comments', [CommunityController::class, 'comment']);
        Route::post('community/posts/{id}/share', [CommunityController::class, 'share']);

    });
});
