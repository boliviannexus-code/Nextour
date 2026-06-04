<?php

namespace App\Providers;

use App\Models\Tour;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Support\LocaleManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        URL::defaults(['locale' => LocaleManager::current()]);

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Route::bind('tour', function (string $value): Tour {
            $query = Tour::query()->withLocale();

            if (ctype_digit($value)) {
                return $query->whereKey((int) $value)->firstOrFail();
            }

            return $query->whereTranslationSlug($value, LocaleManager::current())->firstOrFail();
        });
    }
}
