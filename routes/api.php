<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\UserController;
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
    Route::middleware(['api.key', 'throttle:api-auth'])->group(function () {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/password/reset-token', [AuthController::class, 'resetPasswordByToken']);
    });

    Route::middleware(['api.key', 'auth:sanctum', 'throttle:api-auth'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/profile/avatar', [AuthController::class, 'uploadAvatar']);
        Route::post('/auth/email/verification-notification', [AuthController::class, 'sendVerificationNotification']);
        Route::get('/auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('/auth/password/reset', [AuthController::class, 'resetPassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::middleware('can:manage_users')->group(function () {
            Route::get('/admin/users', [UserController::class, 'index']);
        });
    });
});
