<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * One configuration for the website and both mobile apps.
 *
 * The apps (mobile/customer, mobile/rider) build from mobile/app-config.json. Laravel writes that
 * file from its own settings: the website address (APP_URL), the site name, Admin → Integrations
 * → Google sign-in and Mobile apps. It is rewritten whenever those settings change, after setup
 * and after every update, and the same values are served at /api/v1/app-config so the GitHub
 * build (and the apps at run time) always use what is configured on the live site.
 */
class MobileConfig
{
    public static function path(): string
    {
        return base_path('mobile/app-config.json');
    }

    private static function setting(string $group, string $key, $default = null)
    {
        try {
            return Setting::get($group, $key, $default);
        } catch (Throwable) {
            return $default; // before setup: no database yet
        }
    }

    /** Everything the apps need. Nothing secret: these values ship inside the apps. */
    public static function build(): array
    {
        $m = config('dailytable.mobile');
        $s = fn (string $k, $d = null) => self::setting('mobile', $k, $d) ?: $d;
        $g = fn (string $k) => self::setting('google', $k, $m['google'][$k] ?? null) ?: null;
        $brand = self::setting('app', 'brand') ?: config('dailytable.brand') ?: config('app.name');

        return [
            'api_url' => rtrim((string) config('app.url'), '/'),
            'brand' => $brand,
            'version' => $s('version', $m['version']),
            'customer' => [
                'name' => $s('customer_name', $m['customer']['name']),
                'package' => $s('customer_package', $m['customer']['package']),
                'scheme' => $m['customer']['scheme'],
                'color' => $s('customer_color', $m['customer']['color']),
            ],
            'rider' => [
                'name' => $s('rider_name', $m['rider']['name']),
                'package' => $s('rider_package', $m['rider']['package']),
                'scheme' => $m['rider']['scheme'],
                'color' => $s('rider_color', $m['rider']['color']),
            ],
            'google' => [
                'android_client_id' => $g('android_client_id'),
                'ios_client_id' => $g('ios_client_id'),
                'web_client_id' => $g('web_client_id'),
            ],
            'eas_project_id' => $s('eas_project_id', $m['eas_project_id']) ?: null,
            'support' => [
                'phone' => $s('support_phone', $m['support_phone']) ?: null,
                'whatsapp' => $s('support_whatsapp', $m['support_whatsapp']) ?: null,
                'email' => self::setting('app', 'support_email') ?: config('mail.from.address'),
            ],
            'links' => [
                'android' => self::setting('app', 'android_url') ?: null,
                'ios' => self::setting('app', 'ios_url') ?: null,
                'privacy' => rtrim((string) config('app.url'), '/').'/privacy',
                'terms' => rtrim((string) config('app.url'), '/').'/terms',
            ],
        ];
    }

    /** Write mobile/app-config.json (only when the mobile folder is there and the content changed). */
    public static function write(): bool
    {
        $dir = dirname(self::path());
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }
        $json = json_encode(self::build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
        if (is_file(self::path()) && file_get_contents(self::path()) === $json) {
            return true;
        }

        return file_put_contents(self::path(), $json, LOCK_EX) !== false;
    }
}
