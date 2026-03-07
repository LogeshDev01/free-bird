<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Admin\AuthController;
use App\Http\Controllers\Api\v1\Admin\TrainerClientController;

/*
|--------------------------------------------------------------------------
| Admin API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');

});


Route::middleware(['jwt.cookie', 'auth:api'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    
    // Subscription Management Routes
    Route::apiResource('plans', \App\Http\Controllers\Api\v1\Admin\PlanController::class);
    Route::apiResource('features', \App\Http\Controllers\Api\v1\Admin\FeatureController::class);

    // Workout Management Routes
    Route::apiResource('workout-category-types', \App\Http\Controllers\Api\v1\Admin\WorkoutCategoryTypeController::class);
    Route::apiResource('workouts', \App\Http\Controllers\Api\v1\Admin\WorkoutController::class);
    Route::post('workouts/assign', [\App\Http\Controllers\Api\v1\Admin\WorkoutAssignmentController::class, 'store']);
    Route::patch('workouts/assignments/{id}', [\App\Http\Controllers\Api\v1\Admin\WorkoutAssignmentController::class, 'update']);
    Route::delete('workouts/assignments/{id}', [\App\Http\Controllers\Api\v1\Admin\WorkoutAssignmentController::class, 'destroy']);
    Route::delete('workouts/batch/{batch_id}', [\App\Http\Controllers\Api\v1\Admin\WorkoutAssignmentController::class, 'destroyBatch']);

    // ── Trainer-Client Enrollment Routes ────────────────────────────────
    Route::get('trainer-clients', [TrainerClientController::class, 'index']);
    Route::post('trainer-clients', [TrainerClientController::class, 'assign']);
    Route::patch('trainer-clients/{id}/status', [TrainerClientController::class, 'updateStatus']);
    Route::delete('trainer-clients/{id}', [TrainerClientController::class, 'destroy']);

    // Per-trainer and per-client views
    Route::get('trainers/{trainerId}/clients', [TrainerClientController::class, 'trainerClients']);
    Route::get('clients/{clientId}/trainers', [TrainerClientController::class, 'clientTrainers']);

    // FAQs Management
    Route::apiResource('faqs', \App\Http\Controllers\Api\v1\Admin\FaqController::class);

    // Business Settings
    Route::get('business-settings', [\App\Http\Controllers\Api\v1\Admin\BusinessSettingController::class, 'index']);
    Route::post('business-settings', [\App\Http\Controllers\Api\v1\Admin\BusinessSettingController::class, 'updateSetting']);
});