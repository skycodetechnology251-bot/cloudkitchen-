<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * Admin → Branding: business name, logo and fonts, used by the website, admin panel, login page,
 * invoices and payslips. The name and logo address are also written to the apps' config.
 */
class Brand
{
    /** Google Fonts offered in Admin → Branding: key => [label, CSS stack, Google family spec, style]. */
    public const FONTS = [
        'fraunces' => ['Fraunces', "'Fraunces',Georgia,serif", 'Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700', 'Warm serif'],
        'playfair' => ['Playfair Display', "'Playfair Display',Georgia,serif", 'Playfair+Display:wght@400;600;700;800', 'Elegant serif'],
        'lora' => ['Lora', "'Lora',Georgia,serif", 'Lora:wght@400;500;600;700', 'Bookish serif'],
        'merriweather' => ['Merriweather', "'Merriweather',Georgia,serif", 'Merriweather:wght@400;700;900', 'Sturdy serif'],
        'dmserif' => ['DM Serif Display', "'DM Serif Display',Georgia,serif", 'DM+Serif+Display', 'Bold serif'],
        'baloo' => ['Baloo 2', "'Baloo 2',system-ui,sans-serif", 'Baloo+2:wght@400;500;600;700;800', 'Friendly, rounded'],
        'quicksand' => ['Quicksand', "'Quicksand',system-ui,sans-serif", 'Quicksand:wght@400;500;600;700', 'Soft, rounded'],
        'nunito' => ['Nunito', "'Nunito',system-ui,sans-serif", 'Nunito:wght@400;600;700;800', 'Rounded'],
        'poppins' => ['Poppins', "'Poppins',system-ui,sans-serif", 'Poppins:wght@400;500;600;700;800', 'Geometric'],
        'montserrat' => ['Montserrat', "'Montserrat',system-ui,sans-serif", 'Montserrat:wght@400;500;600;700;800', 'Geometric'],
        'outfit' => ['Outfit', "'Outfit',system-ui,sans-serif", 'Outfit:wght@400;500;600;700;800', 'Modern'],
        'manrope' => ['Manrope', "'Manrope',system-ui,sans-serif", 'Manrope:wght@400;500;600;700;800', 'Clean, modern'],
        'inter' => ['Inter', "'Inter',system-ui,sans-serif", 'Inter:wght@400;500;600;700;800', 'Neutral'],
        'dmsans' => ['DM Sans', "'DM Sans',system-ui,sans-serif", 'DM+Sans:wght@400;500;600;700;800', 'Clean'],
        'worksans' => ['Work Sans', "'Work Sans',system-ui,sans-serif", 'Work+Sans:wght@400;500;600;700;800', 'Plain, readable'],
        'rubik' => ['Rubik', "'Rubik',system-ui,sans-serif", 'Rubik:wght@400;500;600;700;800', 'Slightly rounded'],
        'opensans' => ['Open Sans', "'Open Sans',system-ui,sans-serif", 'Open+Sans:wght@400;600;700;800', 'Very readable'],
        'lato' => ['Lato', "'Lato',system-ui,sans-serif", 'Lato:wght@400;700;900', 'Warm, readable'],
        'roboto' => ['Roboto', "'Roboto',system-ui,sans-serif", 'Roboto:wght@400;500;700', 'Familiar'],
        'mukta' => ['Mukta', "'Mukta',system-ui,sans-serif", 'Mukta:wght@400;500;600;700;800', 'Indian, clear'],
        'hind' => ['Hind', "'Hind',system-ui,sans-serif", 'Hind:wght@400;500;600;700', 'Indian, compact'],
        'notosans' => ['Noto Sans', "'Noto Sans',system-ui,sans-serif", 'Noto+Sans:wght@400;500;600;700;800', 'Works with many scripts'],
    ];

    public const DEFAULT_HEAD = 'fraunces';
    public const DEFAULT_BODY = 'manrope';
    public const DEFAULT_UI = 'poppins';

    /** Font for every dashboard (admin, kitchen board, POS, dietitian): one clean font for headings and text. */
    public static function uiKey(): string
    {
        $k = (string) self::setting('brand', 'font_ui', self::DEFAULT_UI);
        return isset(self::FONTS[$k]) ? $k : self::DEFAULT_UI;
    }

    public static function uiFontUrl(): string
    {
        return 'https://fonts.googleapis.com/css2?family='.self::FONTS[self::uiKey()][2].'&display=swap';
    }

    public static function uiCss(): string
    {
        return ':root{--font-ui:'.self::FONTS[self::uiKey()][1].",'Noto Sans Tamil',sans-serif}";
    }

    private static function setting(string $group, string $key, $default = null)
    {
        try {
            return Setting::get($group, $key, $default);
        } catch (Throwable) {
            return $default; // before install: no database yet
        }
    }

    public static function name(): string
    {
        return (string) (self::setting('app', 'brand') ?: config('dailytable.brand') ?: 'Daily Table');
    }

    /** Public address of the uploaded logo, or null for the built-in leaf mark. */
    public static function logoUrl(): ?string
    {
        $path = self::setting('brand', 'logo_path');
        if (! $path) {
            return null;
        }
        return str_starts_with($path, 'http') || is_file(public_path($path)) ? Media::url($path) : null;
    }

    /** Logo address on the main website address (for the apps' config, not tied to the current request). */
    public static function logoAbsoluteUrl(): ?string
    {
        $path = self::setting('brand', 'logo_path');
        if ($path && str_starts_with($path, 'http')) {
            return $path;
        }
        return $path && is_file(public_path($path)) ? rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/') : null;
    }

    /** Mobile app login screen: rotating photos + headline, changed any time in Admin → Branding. */
    public static function loginScreen(): array
    {
        $paths = json_decode((string) self::setting('brand', 'login_images', '[]'), true) ?: [];
        $images = array_values(array_filter(array_map(fn ($p) => str_starts_with($p, 'http') ? $p : (is_file(public_path($p)) ? rtrim((string) config('app.url'), '/').'/'.ltrim($p, '/') : null), $paths)));
        return [
            'images' => $images, 'paths' => $paths,
            'title' => (string) self::setting('brand', 'login_title', 'Home-style meals, delivered fresh'),
            'subtitle' => (string) self::setting('brand', 'login_subtitle', 'Breakfast, lunch, break-time snacks and dinner from your neighbourhood kitchen.'),
        ];
    }

    public static function showName(): bool
    {
        return (string) self::setting('brand', 'show_name', '1') === '1';
    }

    public static function headKey(): string
    {
        $k = (string) self::setting('brand', 'font_head', self::DEFAULT_HEAD);
        return isset(self::FONTS[$k]) ? $k : self::DEFAULT_HEAD;
    }

    public static function bodyKey(): string
    {
        $k = (string) self::setting('brand', 'font_body', self::DEFAULT_BODY);
        return isset(self::FONTS[$k]) ? $k : self::DEFAULT_BODY;
    }

    /** One stylesheet link per font, so one bad font can never break the other. */
    public static function fontUrls(?string $head = null, ?string $body = null): array
    {
        $keys = array_unique([$head ?? self::headKey(), $body ?? self::bodyKey()]);
        $urls = array_map(fn ($k) => 'https://fonts.googleapis.com/css2?family='.self::FONTS[$k][2].'&display=swap', $keys);
        $urls[] = 'https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@500;600&display=swap';
        return $urls;
    }

    public static function cssVars(): string
    {
        return ':root{--font-head:'.self::FONTS[self::headKey()][1].';--font-body:'.self::FONTS[self::bodyKey()][1].",'Noto Sans Tamil',sans-serif}";
    }
}
