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
            'fonts' => Brand::FONTS, 'head' => Brand::headKey(), 'body' => Brand::bodyKey(),
            'name' => Brand::name(), 'logo' => Brand::logoUrl(), 'showName' => Brand::showName(),
        ]);
    }

    public function update(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:60',
            'font_head' => ['required', Rule::in(array_keys(Brand::FONTS))],
            'font_body' => ['required', Rule::in(array_keys(Brand::FONTS))],
            // SVG is not accepted: it can carry scripts.
            'logo' => 'nullable|file|mimes:png,jpg,jpeg,webp|max:2048|dimensions:min_width=32,min_height=32,max_width=4000,max_height=4000',
        ], ['logo.mimes' => 'Upload the logo as PNG, JPG or WEBP (a transparent PNG looks best).']);

        Setting::put('app', 'brand', trim($d['name']));
        Setting::put('brand', 'font_head', $d['font_head']);
        Setting::put('brand', 'font_body', $d['font_body']);
        Setting::put('brand', 'show_name', $r->boolean('show_name') ? '1' : '0');

        if ($r->boolean('remove_logo')) {
            $this->deleteLogo();
            Setting::put('brand', 'logo_path', null);
        }
        if ($file = $r->file('logo')) {
            @mkdir(public_path(self::DIR), 0755, true);
            $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $name = 'logo-'.Str::lower(Str::random(10)).'.'.$ext;
            $file->move(public_path(self::DIR), $name);
            $this->deleteLogo();
            Setting::put('brand', 'logo_path', self::DIR.'/'.$name);
        }

        MobileConfig::write();
        AuditService::log('settings.branding', null, ['font_head' => $d['font_head'], 'font_body' => $d['font_body'], 'logo' => (bool) Brand::logoUrl()]);
        return back()->with('status', 'Branding saved. The website, admin, invoices and payslips now use it.');
    }

    private function deleteLogo(): void
    {
        $old = Setting::get('brand', 'logo_path');
        if ($old && str_starts_with($old, self::DIR.'/') && is_file(public_path($old))) {
            @unlink(public_path($old));
        }
    }
}
