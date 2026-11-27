<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Support\Install\InstallState;
use App\Support\Install\Upgrader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Admin → System: version, pending database updates, cron status, backups, one-click update. */
class SystemController extends Controller
{
    public function index(): View
    {
        $dir = storage_path(config('dailytable.backups_path'));
        $backups = collect(is_dir($dir) ? glob($dir.'/*.sql.gz') : [])
            ->map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'at' => filemtime($f)])
            ->sortByDesc('at')->take(10)->values();
        $heartbeat = Cache::get('cron.heartbeat');

        return view('admin.system', [
            'codeVersion' => config('dailytable.version'),
            'dbVersion' => Upgrader::installedVersion(),
            'pending' => Upgrader::pendingMigrations(),
            'installedAt' => Setting::get('system', 'installed_at'),
            'upgradedAt' => Setting::get('system', 'upgraded_at'),
            'cronOk' => $heartbeat && now()->diffInSeconds($heartbeat, true) < 150,
            'webCron' => Cache::get('webcron.ran'),
            'backups' => $backups,
            'cron' => '/usr/local/bin/php '.base_path('artisan').' schedule:run >> /dev/null 2>&1',
            'php' => PHP_VERSION,
            'lock' => InstallState::lockInfo(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $steps = [];
        try {
            $result = (new Upgrader)->run(function (string $label, callable $work) use (&$steps) {
                $work();
                $steps[] = $label;
            }, backup: true);
            InstallState::writeLock();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['update' => 'The update stopped: '.$e->getMessage().' Your data was not removed; a backup is in the list below if one was taken.']);
        }
        AuditService::log('system.update', null, ['from' => $result['from'], 'to' => $result['to'], 'migrations' => count($result['migrations'])]);

        // LiteSpeed hosting: clear its page/file cache so everyone gets the new design straight away.
        return back()->with('status', 'Updated to '.$result['to'].'. '.count($result['migrations']).' database update(s) applied'.($result['backup'] ? ', backup saved first.' : '.'))
            ->header('X-LiteSpeed-Purge', '*');
    }

    public function backup(string $file): BinaryFileResponse
    {
        $path = storage_path(config('dailytable.backups_path').'/'.basename($file));
        abort_unless(is_file($path) && str_ends_with($path, '.sql.gz'), 404);

        return response()->download($path);
    }
}
