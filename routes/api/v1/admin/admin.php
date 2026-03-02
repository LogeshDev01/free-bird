<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\Admin\AuthController;

/*
|--------------------------------------------------------------------------
| Admin API Routes (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');

});


Route::middleware(['jwt.cookie'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Admin dashboard access granted',
            'admin' => auth()->user()
        ]);
    });

});