<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\EnforceIpRules::class);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\LogApiRequests::class,
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureIsAdmin::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // On a validation failure Laravel flashes the old input into the session
        // (SESSION_DRIVER=database, SESSION_ENCRYPT=false — i.e. plain text in the
        // sessions table). Merged on top of the framework defaults
        // (password / current_password / password_confirmation):
        //   mail_password — the user's Gmail App Password, encrypted everywhere
        //                   else; flashing it would defeat that entirely.
        //   code          — the 2FA field, which also accepts long-lived
        //                   recovery codes, not just a 30-second TOTP.
        $exceptions->dontFlash([
            'mail_password',
            'code',
        ]);
    })->create();
