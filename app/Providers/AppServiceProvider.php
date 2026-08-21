<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::define('manage_users', fn (User $user): bool =>
            $user->hasRole('admin')
            || $user->permissions()->where('name', 'manage_users')->exists()
            || $user->roles()->whereHas('permissions', fn ($query) => $query->where('name', 'manage_users'))->exists()
        );

        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
