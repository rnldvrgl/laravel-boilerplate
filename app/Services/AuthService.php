<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function register(array $attributes): User
    {
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

    public function resetPassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }
}
