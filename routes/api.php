<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1/admin')->group(base_path('routes/api/v1/admin/admin.php'));
Route::prefix('v1/mobile')->group(function () {
    foreach (glob(base_path('routes/api/v1/mobile/*.php')) as $file) {
        require $file;
    }
});
