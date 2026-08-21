<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

class AuthService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function register(array $attributes): User
    {
        // Register the user and keep password hashing inside the service layer.
        $user = User::create([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'password' => Hash::make($attributes['password']),
        ]);

        return $user;
    }

    public function login(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function updateProfile(User $user, array $attributes): User
    {
        $user->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ]);

        $user->save();

        return $user->fresh();
    }

    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        $path = $file->storePubliclyAs('avatars', $user->id.'-'.time().'.'.$file->extension(), 'public');

        $user->avatar_path = $path;
        $user->save();

        return $user->fresh();
    }

    public function sendVerificationNotification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    public function resetPassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public function resetPasswordByToken(array $attributes): bool
    {
        $status = Password::reset($attributes, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        });

        return $status === Password::PASSWORD_RESET;
    }
}
