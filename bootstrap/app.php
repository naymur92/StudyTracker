<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.headers' => \App\Http\Middleware\ApiHeadersCheck::class,
            'api.token.headers' => \App\Http\Middleware\TokenApiHeadersCheck::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
            'deny.demo' => \App\Http\Middleware\DemoUserRestriction::class,
        ]);
        $middleware->api(append: ['api.headers']);
        $middleware->web(append: ['log.activity']);

        $middleware->validateCsrfTokens(except: [
            'oauth-admin-app/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
