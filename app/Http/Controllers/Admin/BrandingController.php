<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Support\Brand;
use App\Support\MobileConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Admin → Branding: business name, logo and fonts for the website, admin, invoices and payslips. */
class BrandingController extends Controller
{
    private const DIR = 'uploads/brand';

    public function show()
    {
        return view('admin.branding', [
            'fonts' => Brand::FONTS, 'head' => Brand::headKey(), 'body' => Brand::bodyKey(), 'ui' => Brand::uiKey(),
            'name' => Brand::name(), 'logo' => Brand::logoUrl(), 'showName' => Brand::showName(), 'login' => Brand::loginScreen(),
        ]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:60',
            'font_head' => ['required', Rule::in(array_keys(Brand::FONTS))],
            'font_body' => ['required', Rule::in(array_keys(Brand::FONTS))],
            'font_ui' => ['required', Rule::in(array_keys(Brand::FONTS))],
            // SVG is not accepted: it can carry scripts.
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048|dimensions:min_width=32,min_height=32,max_width=4000,max_height=4000',
        ], ['logo.mimes' => 'Upload the logo as PNG, JPG or WEBP (a transparent PNG looks best).']);

        Setting::put('app', 'brand', trim($d['name']));
        Setting::put('brand', 'font_head', $d['font_head']);
        Setting::put('brand', 'font_body', $d['font_body']);
        Setting::put('brand', 'font_ui', $d['font_ui']);
        Setting::put('brand', 'show_name', $r->boolean('show_name') ? '1' : '0');

        if ($r->boolean('remove_logo')) {
            \App\Support\Media::delete(Setting::get('brand', 'logo_path'));
            Setting::put('brand', 'logo_path', null);
        }
        if ($file = $r->file('logo')) {
            // Google Drive when connected, otherwise public/uploads/brand.
            Setting::put('brand', 'logo_path', \App\Support\Media::store($file, 'brand', Setting::get('brand', 'logo_path')));
        }

        MobileConfig::write();
        AuditService::log('settings.branding', null, ['font_head' => $d['font_head'], 'font_body' => $d['font_body'], 'logo' => (bool) Brand::logoUrl()]);
        return back()->with('status', 'Branding saved. The website, admin, invoices and payslips now use it.');
    }

    /** Mobile app login screen: up to 5 photos that rotate, plus the headline and the line under it. */
    public function loginScreen(Request $r)
    {
        $d = $r->validate([
            'login_title' => 'required|string|max:70', 'login_subtitle' => 'nullable|string|max:140',
            'photos' => 'nullable|array|max:5', 'photos.*' => 'file|mimes:jpg,jpeg,png,webp|max:4096|dimensions:min_width=400,min_height=400',
            'remove' => 'nullable|array', 'remove.*' => 'string',
        ], ['photos.*.mimes' => 'Photos must be JPG, PNG or WEBP.', 'photos.*.dimensions' => 'Use photos at least 400 × 400 pixels (tall photos look best).']);
        $paths = json_decode((string) Setting::get('brand', 'login_images', '[]'), true) ?: [];
        foreach ($d['remove'] ?? [] as $p) {
            if (in_array($p, $paths, true)) {
                \App\Support\Media::delete($p);
                $paths = array_values(array_diff($paths, [$p]));
            }
        }
        foreach ($r->file('photos', []) as $file) {
            if (count($paths) >= 5) {
                break;
            }
            $paths[] = \App\Support\Media::store($file, 'app-login');
        }
        Setting::put('brand', 'login_images', json_encode(array_values($paths)));
        Setting::put('brand', 'login_title', trim($d['login_title']));
        Setting::put('brand', 'login_subtitle', trim((string) ($d['login_subtitle'] ?? '')));
        AuditService::log('settings.app_login', null, ['photos' => count($paths)]);
        return back()->with('status', 'App login screen saved. The apps show it the next time they open.');
    }

    private function deleteLogo(): void
    {
        $old = Setting::get('brand', 'logo_path');
        if ($old && str_starts_with($old, self::DIR.'/') && is_file(public_path($old))) {
            @unlink(public_path($old));
        }
    }
}
