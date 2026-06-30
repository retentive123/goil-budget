<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {
            RateLimiter::for('api', function ($request) {
                return $request->user()
                    ? Limit::perMinute(120)->by($request->user()->id)
                    : Limit::perMinute(20)->by($request->ip());
            });

            RateLimiter::for('login', function ($request) {
                return Limit::perMinute(5)->by($request->ip());
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureUserIsActive::class,
            \App\Http\Middleware\LogLastLogin::class,
            \App\Http\Middleware\EnforceSessionTimeout::class,
            \App\Http\Middleware\EnforceSingleSession::class,
            \App\Http\Middleware\EnforceSegregationOfDuties::class,
        ]);

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
