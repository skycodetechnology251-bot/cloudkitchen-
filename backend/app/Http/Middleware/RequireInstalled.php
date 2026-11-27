<?php

namespace App\Http\Middleware;

use App\Support\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Before Daily Table is set up, every page sends the visitor to the setup wizard at /install
 * (the apps get a 503 explaining it), instead of a blank page or a database error.
 */
class RequireInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallState::installed() || $request->routeIs('install.*') || $request->is('up')) {
            return $next($request);
        }
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Daily Table is not set up yet. Open /install in a browser.', 'code' => 'NOT_INSTALLED'], 503);
        }

        return redirect()->route('install.show');
    }
}
