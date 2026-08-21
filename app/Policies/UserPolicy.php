<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function manageUsers(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->permissions()->where('name', 'manage_users')->exists()
            || $user->roles()->whereHas('permissions', function ($query) {
                $query->where('name', 'manage_users');
            })->exists();
    }

    public function manage_users(User $user): bool
    {
        return $this->manageUsers($user);
    }
}
