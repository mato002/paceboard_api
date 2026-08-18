<?php

namespace App\Providers;

use App\Models\UserNotification;
use App\Observers\UserNotificationObserver;
use App\Support\DatabaseBootstrapper;
use App\View\Composers\AdminLayoutComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        DatabaseBootstrapper::ensureReady();

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        RateLimiter::for('otp', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('admin-login', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));

        UserNotification::observe(UserNotificationObserver::class);

        View::composer('admin.layout', AdminLayoutComposer::class);

        Paginator::defaultView('admin.partials.pagination');
        Paginator::defaultSimpleView('admin.partials.pagination-simple');
    }
}
