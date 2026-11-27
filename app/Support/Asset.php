<?php

namespace App\Support;

/**
 * Stylesheet / script links with a version number that changes whenever the file changes,
 * so browsers and hosting caches (LiteSpeed, Cloudflare…) always load the latest design after an update.
 */
class Asset
{
    /** Bump together with the marker at the top of public/css/dt.css and site.css. */
    public const CSS_MARK = 'dt-css 1.13';

    public static function v(string $path): string
    {
        $file = public_path($path);
        $ver = is_file($file) ? filemtime($file) : config('dailytable.version');
        return asset($path).'?v='.$ver;
    }

    /** True when the stylesheet on the server is older than this version of the code. */
    public static function staleCss(): bool
    {
        $file = public_path('css/dt.css');
        if (! is_file($file)) {
            return true;
        }
        $head = (string) file_get_contents($file, false, null, 0, 200);
        return ! str_contains($head, self::CSS_MARK);
    }
}
