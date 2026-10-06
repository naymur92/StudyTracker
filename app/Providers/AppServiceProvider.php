<?php

namespace App\Providers;

use App\Models\DataDeletionRequest;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Disable Passport's built-in routes
        Passport::ignoreRoutes();

        $this->app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        // Pending data deletion requests badge in the admin sidebar.
        View::composer('layouts.sidebar', function ($view) {
            $view->with('pendingDataRequestCount', auth()->user()?->can('data-request-list')
                ? DataDeletionRequest::status(DataDeletionRequest::STATUS_PENDING)->count()
                : null);
        });

        // Conservative API rate limits for low-resource deployments (e.g., AWS free tier).
        RateLimiter::for('auth-token', function (Request $request) {
            return Limit::perMinute(8)->by('auth-token:' . $request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by('auth-register:' . $request->ip());
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            return Limit::perMinute(20)->by('auth-refresh:' . $request->ip());
        });

        RateLimiter::for('auth-verify', function (Request $request) {
            return Limit::perMinute(6)->by('auth-verify:' . $request->ip());
        });

        RateLimiter::for('auth-forgot', function (Request $request) {
            return Limit::perMinute(5)->by('auth-forgot:' . $request->ip());
        });

        RateLimiter::for('study-read', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(60)->by('study-read:' . $key);
        });

        RateLimiter::for('study-write', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(30)->by('study-write:' . $key);
        });

        RateLimiter::for('api-profile', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(30)->by('api-profile:' . $key);
        });

        // Passport will use default storage/oauth keys location
        // Passport::loadKeysFrom(storage_path(''));

        Passport::enablePasswordGrant();

        Passport::tokensExpireIn(CarbonInterval::hours(3));
        Passport::refreshTokensExpireIn(CarbonInterval::days(15));
        Passport::personalAccessTokensExpireIn(CarbonInterval::months(6));
    }
}
