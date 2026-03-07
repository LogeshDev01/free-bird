<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Mobile\Trainer\AuthController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\DashboardController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\ProfileController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\ClientController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\SessionController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\WorkoutController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\DietPlanController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\NotificationController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\SlotController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\HistoryController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\TrainerController;
use App\Http\Controllers\Api\v1\Mobile\Trainer\LeaveController;
use App\Http\Controllers\Api\v1\Mobile\WaterLogController;
use App\Http\Controllers\Api\v1\Mobile\CommunityController;
use App\Http\Controllers\Api\v1\Mobile\SupportController;

Route::prefix('trainer')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 🔓 PUBLIC (No Auth Required)
    |--------------------------------------------------------------------------
    */
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);

    // ── Support / FAQs ────────────────────────────────
    Route::get('faqs', [SupportController::class, 'getFaqs']);
    Route::get('pages/{key}', [SupportController::class, 'getPage']);

    /*
    |--------------------------------------------------------------------------
    | 🔒 PROTECTED (Auth Required)
    |--------------------------------------------------------------------------
    */
    Route::post('refresh', [AuthController::class, 'refresh']);
    
    Route::middleware('auth:trainer')->group(function () {

        // ── Auth ────────────────────────────────────────
        Route::post('logout', [AuthController::class, 'logout']);

        // ── Dashboard & History ───────────────────────
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('history', [HistoryController::class, 'index']);
        Route::get('employment-info', [TrainerController::class, 'employmentInfo']);

        // ── Profile ────────────────────────────────────
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::post('profile/upload-pic', [ProfileController::class, 'uploadProfilePic']);

        // ── Clients ────────────────────────────────────
        // ⚠️ Static routes BEFORE dynamic {id} routes
        Route::get('clients/today', [ClientController::class, 'today']);
        Route::get('clients/sessions', [ClientController::class, 'clientSessions']);
        Route::get('clients', [ClientController::class, 'index']);
        Route::get('clients/{id}', [ClientController::class, 'show']);

        // ── Sessions ───────────────────────────────────
        // ⚠️ Static routes BEFORE dynamic {id} routes
        Route::get('sessions/today', [SessionController::class, 'today']);
        Route::get('sessions/upcoming', [SessionController::class, 'upcoming']);
        Route::get('sessions', [SessionController::class, 'index']);
        Route::post('sessions', [SessionController::class, 'store']);
        Route::put('sessions/{id}/status', [SessionController::class, 'updateStatus']);

        // ── Workouts Library ───────────────────────────
        // ⚠️ Static routes BEFORE dynamic {id} routes
        Route::get('workouts/categories', [WorkoutController::class, 'categories']);
        Route::get('workouts', [WorkoutController::class, 'index']);
        Route::get('workouts/{id}', [WorkoutController::class, 'show']);
        Route::post('workouts/assign', [WorkoutController::class, 'assign']);
        Route::patch('workouts/assignments/{id}', [WorkoutController::class, 'updateAssignment']);
        Route::delete('workouts/assignments/{id}', [WorkoutController::class, 'removeAssignment']);
        Route::delete('workouts/batch/{batch_id}', [WorkoutController::class, 'removeBatchAssignment']);

        // ── Diet Plans Library ─────────────────────────
        // ⚠️ Static routes BEFORE dynamic {id} routes
        Route::get('diet-plans/categories', [DietPlanController::class, 'categories']);
        Route::get('diet-plans/meal-types', [DietPlanController::class, 'mealTypes']);
        Route::get('diet-plans', [DietPlanController::class, 'index']);
        Route::get('diet-plans/{id}', [DietPlanController::class, 'show']);
        Route::post('diet-plans/assign', [DietPlanController::class, 'assign']);

        // ── Notifications ──────────────────────────────
        // ⚠️ Static routes BEFORE dynamic {id} routes
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read', [NotificationController::class, 'markAsRead']);

        // ── Slots ──────────────────────────────────────
        Route::get('slots/types', [SlotController::class, 'types']);
        Route::get('slots', [SlotController::class, 'index']);
        Route::post('slots', [SlotController::class, 'store']);
        Route::put('slots/{id}', [SlotController::class, 'update']);
        Route::delete('slots/{id}', [SlotController::class, 'destroy']);

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

        // ── Leaves & Vacation ──────────────────────────
        Route::get('leaves/summary', [LeaveController::class, 'summary']);
        Route::get('leaves/types', [LeaveController::class, 'types']);
        Route::get('leaves', [LeaveController::class, 'index']);
        Route::post('leaves', [LeaveController::class, 'store']);
    });
});
