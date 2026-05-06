<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(fn () => route('workspace'));

        // Superadmin y owner del proyecto activo tienen todos los permisos
        Gate::before(function ($user, $ability) {
            if ($user->is_superadmin) {
                return true;
            }
            $project = app()->bound('active_project') ? app('active_project') : null;
            if ($project && $project->owner_id === $user->id) {
                return true;
            }
        });
    }
}
