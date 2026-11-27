<?php

use App\Support\Install\InstallState;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// A fresh upload has no .env yet: create it (with a new APP_KEY) so the setup page can open.
InstallState::prepareEnvironment(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'perm' => \App\Http\Middleware\RequirePermission::class,
            'role' => \App\Http\Middleware\RequireRole::class,
            'before-install' => \App\Http\Middleware\OnlyBeforeInstall::class,
        ]);
        // "Is Daily Table installed?" runs first on every page and API call, so a new site shows the
        // setup wizard instead of a database error.
        $middleware->web(prepend: [\App\Http\Middleware\RequireInstalled::class]);
        $middleware->api(prepend: [\App\Http\Middleware\RequireInstalled::class]);
        // Background tasks for hosting without a cron job (runs after the response is sent).
        $middleware->append(\App\Http\Middleware\WebCron::class);
        // Hosting panels put a proxy in front of PHP: trust it so https links and client IPs are right.
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo('/staff/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());

        // New files uploaded but the database update has not run yet: explain instead of a 500.
        $exceptions->render(function (\Illuminate\Database\QueryException $e, Request $request) {
            $missing = in_array($e->errorInfo[0] ?? null, ['42S02', '42S22'], true)
                || preg_match('/Base table or view not found|Unknown column|no such table|no such column/i', $e->getMessage());
            try {
                if (! $missing || ! InstallState::installed() || ! \App\Support\Install\Upgrader::pendingMigrations()) {
                    return null;
                }
            } catch (\Throwable) {
                return null;
            }
            report($e);

            return ($request->expectsJson() || $request->is('api/*'))
                ? response()->json(['message' => 'The server is finishing an update. Try again in a minute.', 'code' => 'UPDATE_REQUIRED'], 503)
                : response()->view('install.update-needed', [], 503);
        });
    })->create();
