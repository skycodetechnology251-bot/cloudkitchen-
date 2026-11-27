<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->role && ($user->role->slug === 'admin' || in_array($user->role->slug, $roles, true)), 403);
        return $next($request);
    }
}
