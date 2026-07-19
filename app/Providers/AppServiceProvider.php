<?php

namespace App\Providers;

use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\Permissions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class, fn () => new CurrentTenant());
    }

    public function boot(): void
    {
        // Super users bypass every gate.
        Gate::before(function (User $user) {
            if ($user->role === User::ROLE_SUPER) {
                return true;
            }
            return null;
        });

        foreach (Permissions::all() as $perm) {
            Gate::define($perm, function (User $user) use ($perm) {
                return Permissions::userHas($user, $perm);
            });
        }
    }
}
