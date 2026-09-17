<?php

namespace App\Providers;

use App\Models\Station;
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
        $this->app->singleton(\App\Services\DashboardService::class);
        $this->app->singleton(\App\Services\ReportService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));

        foreach (config('permissions.permissions', []) as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermissionTo($permission));
        }

        // A station scope gate used to guard record-level access.
        Gate::define('access-station', function ($user, Station $station) {
            if ($user->isSuperAdmin() || $user->hasAnyRole(['head_office_admin', 'accountant', 'auditor', 'inventory_officer'])) {
                return true;
            }

            return $station->users()->whereKey($user->id)->exists() || $user->station_id === $station->id;
        });

        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}