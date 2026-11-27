<?php

namespace App\Http\Middleware;

use App\Support\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The setup wizard exists only until Daily Table is installed; afterwards /install is a 404. */
class OnlyBeforeInstall
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(InstallState::installed(), 404);

        return $next($request);
    }
}
