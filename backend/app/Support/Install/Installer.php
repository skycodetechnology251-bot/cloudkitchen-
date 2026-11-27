<?php

namespace App\Support\Install;

use App\Models\Kitchen;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use RuntimeException;
use Throwable;

/**
 * The install steps shared by the browser wizard (/install) and `php artisan dailytable:install`:
 * optional database creation → connection check → .env → tables, roles → admin → demo data → lock.
 * Running it on a database that already has Daily Table updates it (backup first, data kept).
 */
class Installer
{
    public static function passwordRules(): array
    {
        return ['required', 'string', 'max:200', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers(),
            function ($attr, $value, $fail) {
                if (in_array(strtolower((string) $value), ['changeme!2026', 'password123', 'admin12345', 'dailytable123'], true)) {
                    $fail('This password is a published example. Choose your own.');
                }
            }];
    }

    /** MySQL 8.0+ or MariaDB 10.4+. */
    public static function supportedServer(string $version): bool
    {
        if (stripos($version, 'mariadb') !== false) {
            preg_match('/(\d+\.\d+\.\d+)-mariadb/i', $version, $m);

            return version_compare($m[1] ?? $version, '10.4.0', '>=');
        }

        return version_compare($version, '8.0.0', '>=');
    }

    public static function mysqlReason(Throwable $e): string
    {
        $m = $e->getMessage();

        return match (true) {
            str_contains($m, '1045') || str_contains($m, 'Access denied') => 'the user name or password is wrong.',
            str_contains($m, '1049') || str_contains($m, 'Unknown database') => 'the database does not exist. Create it in your hosting panel (cPanel → MySQL Databases), or tick "Create the database for me".',
            str_contains($m, '2002') || str_contains($m, 'Connection refused') || str_contains($m, 'getaddrinfo') => 'MySQL is not reachable at that host and port. On most hosting the host is "localhost".',
            str_contains($m, '1044') => 'this user has no rights on that database. In cPanel → MySQL Databases, add the user to the database with ALL PRIVILEGES.',
            default => (string) preg_replace('/^SQLSTATE\[[^\]]+\]\s*(\[\d+\])?\s*/', '', (string) strtok($m, "\n")),
        };
    }

    /**
     * Connects with the given details on a scratch connection, then makes them the app's connection.
     * @param  array{host: string, port: int, database: string, username: string, password: string}  $db
     * @return string server version
     */
    public static function useDatabase(array $db): string
    {
        $candidate = array_merge(config('database.connections.mysql'), $db);
        config(['database.connections.install_check' => $candidate]);
        try {
            $version = (string) DB::connection('install_check')->selectOne('select version() as v')->v;
        } finally {
            DB::purge('install_check');
            config(['database.connections.install_check' => null]);
        }
        if (! self::supportedServer($version)) {
            throw new RuntimeException("MySQL {$version} is too old. Daily Table needs MySQL 8.0+ or MariaDB 10.4+.");
        }
        config(['database.default' => 'mysql', 'database.connections.mysql' => $candidate]);
        DB::purge('mysql');

        return $version;
    }

    /** Settings written to .env for a live shared-hosting site. */
    public static function envFor(array $db, string $url, bool $https, string $name): array
    {
        return [
            'APP_NAME' => $name,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($url, '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $db['host'],
            'DB_PORT' => (string) $db['port'],
            'DB_DATABASE' => $db['database'],
            'DB_USERNAME' => $db['username'],
            'DB_PASSWORD' => $db['password'],
            'SESSION_DRIVER' => 'database',
            // Secure-only cookies need HTTPS; on plain http they would make sign-in impossible.
            'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'sync',
            'OTP_TEST_NUMBERS' => '',
            'OTP_TEST_CODE' => '',
        ];
    }

    /**
     * Tables, roles, admin, demo data, lock. The database connection must already be set (useDatabase).
     *
     * @param  callable(string $step, callable $work): void  $task
     * @return array{updated: bool, created: bool, email: string, demo_password: ?string, backup: ?string}
     */
    public function run(callable $task, array $admin, bool $demo): array
    {
        @set_time_limit(600);
        $existing = Upgrader::isInstalled();
        $result = (new Upgrader)->run($task, backup: $existing, recordVersion: false);

        // Admin: created only when the database has none (an update keeps the existing one).
        $email = strtolower($admin['email']);
        $created = false;
        if (! Upgrader::adminExists()) {
            $task('Creating your admin account', function () use ($admin, $email, &$created) {
                if (User::where('email', $email)->exists()) {
                    throw new RuntimeException('This email already belongs to another account in this database. Use a different email.');
                }
                $u = User::create(['name' => $admin['name'], 'email' => $email]);
                $u->forceFill(['password' => $admin['password'], 'role_id' => Role::where('slug', 'admin')->value('id'), 'email_verified_at' => now()])->save();
                $created = true;
            });
        } else {
            $email = (string) User::where('role_id', Role::where('slug', 'admin')->value('id'))->orderBy('id')->value('email');
        }

        $demoPassword = null;
        if ($demo && ! Kitchen::where('code', 'ADY')->exists()) {
            $task('Adding demo kitchen, menu and staff', function () use (&$demoPassword) {
                DemoSeeder::$password = null;
                Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
                $demoPassword = DemoSeeder::$password;
            });
        }

        $task('Linking uploaded photos', function () {
            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
                // some hosts forbid symlinks; uploads then fall back to /storage copies
            }
        });

        Upgrader::recordVersion($existing ? Upgrader::installedVersion() : null);
        InstallState::writeLock();

        return ['updated' => $existing, 'created' => $created, 'email' => $email, 'demo_password' => $demoPassword, 'backup' => $result['backup']];
    }
}
