<?php

namespace App\Support\Install;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Brings a Daily Table database up to the code's version:
 * backup → migrations → roles/permissions sync → version record → caches.
 * Never drops or truncates data; migrations only add tables and columns.
 */
class Upgrader
{
    /** True when the connected database already holds Daily Table (an admin account or a recorded version). */
    public static function isInstalled(): bool
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasTable('settings')) {
            return false;
        }

        return Setting::get('system', 'app_version') !== null || self::adminExists();
    }

    public static function adminExists(): bool
    {
        $role = Role::where('slug', 'admin')->value('id');

        return $role && User::where('role_id', $role)->exists();
    }

    public static function installedVersion(): ?string
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        return Setting::get('system', 'app_version') ?? (self::isInstalled() ? '1.0.0' : null);
    }

    public static function recordVersion(?string $from = null): void
    {
        $to = (string) config('dailytable.version');
        Setting::put('system', 'app_version', $to);
        Setting::put('system', 'installed_at', Setting::get('system', 'installed_at') ?? now()->toIso8601String());
        if ($from !== null && $from !== $to) {
            Setting::put('system', 'upgraded_at', now()->toIso8601String());
        }
    }

    /** @return list<string> migration names not yet applied */
    public static function pendingMigrations(): array
    {
        $migrator = app('migrator');
        $files = $migrator->getMigrationFiles([database_path('migrations')]);
        $ran = Schema::hasTable('migrations') ? $migrator->getRepository()->getRan() : [];

        return array_values(array_diff(array_keys($files), $ran));
    }

    /**
     * @param  callable(string $step, callable $work): void  $task  progress reporter
     * @return array{from: ?string, to: string, migrations: list<string>, backup: ?string, backup_method: ?string}
     */
    public function run(callable $task, bool $backup = true, bool $recordVersion = true): array
    {
        $from = self::installedVersion();
        $to = (string) config('dailytable.version');
        $pending = self::pendingMigrations();
        $file = null;
        $method = null;

        if ($backup && $pending !== [] && Schema::hasTable('migrations')) {
            $task('Backing up the database', function () use (&$file, &$method) {
                $dumper = new DatabaseBackup;
                $file = $dumper->dump(storage_path(config('dailytable.backups_path')));
                $method = $dumper->method;
            });
        }

        $task(count($pending).' database update(s)', function () use ($pending) {
            if ($pending !== [] && Artisan::call('migrate', ['--force' => true]) !== 0) {
                throw new \RuntimeException(trim(Artisan::output()));
            }
        });

        // updateOrCreate: adds new roles and permissions; never removes data or your role changes.
        $task('Roles and permissions', function () {
            if (Artisan::call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) !== 0) {
                throw new \RuntimeException(trim(Artisan::output()));
            }
        });

        if ($recordVersion) {
            $task('Recording version '.$to, fn () => self::recordVersion($from));
        }

        $task('Mobile app settings', fn () => \App\Support\MobileConfig::write());

        $task('Refreshing caches', function () {
            $cached = file_exists(base_path('bootstrap/cache/config.php'));
            Artisan::call('optimize:clear');
            if ($cached) {
                Artisan::call('config:cache');
                Artisan::call('route:cache');
            }
        });

        return ['from' => $from, 'to' => $to, 'migrations' => $pending, 'backup' => $file, 'backup_method' => $method];
    }
}
