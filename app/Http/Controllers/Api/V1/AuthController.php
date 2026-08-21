<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ResetPasswordTokenRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterUserRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
        ], status: 201);
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->email, $request->password);

        if ($user === null) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function me(): JsonResponse
    {
        $user = Auth::user();

        return ApiResponse::success([
            'user' => $user ? new UserResource($user) : null,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile(Auth::user(), $request->validated());

        return ApiResponse::success([
            'user' => new UserResource($user),
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $this->authService->uploadAvatar(Auth::user(), $request->file('avatar'));

        return ApiResponse::success([
            'user' => new UserResource($user),
            'avatar_url' => $user->avatar_url,
        ]);
    }

    public function sendVerificationNotification(): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::error('Email is already verified.', 400);
        }

        $this->authService->sendVerificationNotification($user);

        return ApiResponse::success(message: 'Verification email sent.');
    }

    public function verifyEmail(EmailVerificationRequest $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return ApiResponse::success(message: 'Email already verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return ApiResponse::success(message: 'Email verified successfully.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $updated = $this->authService->resetPassword(
            Auth::user(),
            $request->current_password,
            $request->password,
        );

        if (! $updated) {
            return ApiResponse::error('Current password is incorrect.', 400);
        }

        return ApiResponse::success(message: 'Password updated successfully.');
    }

    public function resetPasswordByToken(ResetPasswordTokenRequest $request): JsonResponse
    {
        $updated = $this->authService->resetPasswordByToken($request->validated());

        if (! $updated) {
            return ApiResponse::error('Unable to reset password.', 400);
        }

        return ApiResponse::success(message: 'Password reset successfully.');
    }

    public function logout(): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            $user->tokens()->delete();
        }

        return ApiResponse::success(message: 'Logged out successfully.');
    }
}
