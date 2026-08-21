<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'data' => [
            'status' => 'ok',
        ],
    ]);
});

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'throttle:api-auth'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });
});
