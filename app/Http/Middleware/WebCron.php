<?php

namespace App\Http\Middleware;

use App\Support\Install\InstallState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * For hosting without a cron job: after a page or API response has been sent, run the scheduler
 * (release unpaid reservations, reminders) at most once a minute. When a real cron job is running
 * (it records a heartbeat every minute), this does nothing.
 */
class WebCron
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('dailytable.web_cron') || ! InstallState::installed() || app()->runningUnitTests()) {
            return;
        }
        try {
            $heartbeat = Cache::get('cron.heartbeat');
            if ($heartbeat && now()->diffInSeconds($heartbeat, true) < 150) {
                return; // a real cron job is working
            }
            if (! Cache::add('webcron.ran', now()->toIso8601String(), 60)) {
                return; // ran within the last minute
            }
            app()->instance('webcron.running', true);
            Artisan::call('schedule:run');
        } catch (Throwable $e) {
            report($e);
        }
    }
}
