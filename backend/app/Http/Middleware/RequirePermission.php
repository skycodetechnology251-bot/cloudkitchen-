<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Usage: ->middleware('perm:orders.view') — checked on the server, never trusts the UI. */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 401);
        $user->loadMissing('role.permissions');
        foreach ($permissions as $p) {
            if ($user->hasPermission($p)) {
                return $next($request);
            }
        }
        abort(403, 'You do not have permission to do this.');
    }
}
