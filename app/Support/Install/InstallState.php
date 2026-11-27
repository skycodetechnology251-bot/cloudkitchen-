<?php

namespace App\Support\Install;

/**
 * Whether this copy of Daily Table has been set up. Both installers (the browser wizard at /install
 * and `php artisan dailytable:install`) write the lock file; DAILYTABLE_INSTALLED=true|false overrides it.
 */
class InstallState
{
    public static function installed(): bool
    {
        $forced = config('dailytable.installed');
        if ($forced !== null) {
            return (bool) $forced;
        }

        return is_file(self::lockPath());
    }

    public static function lockPath(): string
    {
        return storage_path(config('dailytable.install_lock', 'app/private/installed.lock'));
    }

    public static function envPath(): string
    {
        return base_path('.env');
    }

    /**
     * Runs before Laravel reads .env: on a fresh upload there is no .env yet, so make one from the
     * shared-hosting template with a new APP_KEY. That lets the setup page itself work (sessions, CSRF).
     */
    public static function prepareEnvironment(string $basePath): void
    {
        $env = $basePath.'/.env';
        if (! is_file($env)) {
            $example = $basePath.'/.env.example';
            if (! is_file($example) || ! @copy($example, $env)) {
                return; // not writable: the setup page explains it
            }
        }
        $content = (string) @file_get_contents($env);
        if (! preg_match('/^APP_KEY=\S+/m', $content)) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            $content = preg_match('/^APP_KEY=.*$/m', $content)
                ? preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $content, 1)
                : rtrim($content)."\nAPP_KEY={$key}\n";
            @file_put_contents($env, $content, LOCK_EX);
            @chmod($env, 0640);
        }
    }

    /** Read one value from .env (quotes removed). */
    public static function envValue(string $key, ?string $path = null): ?string
    {
        $path ??= self::envPath();
        if (is_file($path) && preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', (string) file_get_contents($path), $m)) {
            $v = trim($m[1]);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && $v[-1] === $v[0]) {
                $v = stripcslashes(substr($v, 1, -1));
            }

            return $v === '' ? null : $v;
        }

        return null;
    }

    /** Set values in .env, replacing existing (or commented-out) lines and appending new ones. */
    public static function writeEnv(array $values, ?string $path = null): void
    {
        $path ??= self::envPath();
        if (! is_file($path)) {
            is_file(base_path('.env.example')) ? copy(base_path('.env.example'), $path) : touch($path);
        }
        $content = (string) file_get_contents($path);
        foreach ($values as $key => $value) {
            $value = (string) $value;
            // A line break would let a value inject extra settings into .env.
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', (string) $key) || preg_match('/[\r\n\0]/', $value)) {
                throw new \InvalidArgumentException("Invalid value for {$key}.");
            }
            $escaped = $value === '' || preg_match('/^[A-Za-z0-9_.:\/@+\-=]+$/', $value) ? $value : '"'.addcslashes($value, '"\\$').'"';
            $line = "{$key}={$escaped}";
            $pattern = '/^#?\s*'.preg_quote($key, '/').'=.*$/m';
            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content, 1)
                : rtrim($content)."\n{$line}\n";
        }
        file_put_contents($path, $content, LOCK_EX);
        @chmod($path, 0640); // secrets: not world-readable
    }

    public static function writeLock(): void
    {
        $lock = self::lockPath();
        @mkdir(dirname($lock), 0775, true);
        $previous = is_file($lock) ? (json_decode((string) file_get_contents($lock), true) ?: []) : [];
        file_put_contents($lock, json_encode([
            'version' => config('dailytable.version'),
            'installed_at' => $previous['installed_at'] ?? now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));
    }

    /** @return array{version?: string, installed_at?: string, updated_at?: string} */
    public static function lockInfo(): array
    {
        return is_file(self::lockPath()) ? (json_decode((string) file_get_contents(self::lockPath()), true) ?: []) : [];
    }
}
