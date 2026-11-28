<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Dish;
use App\Models\Setting;
use App\Services\AuditService;
use App\Support\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Connect Google Drive for photo storage, and move existing photos there. */
class DriveController extends Controller
{
    public function connect(Request $r)
    {
        abort_unless(GoogleDrive::configured(), 422, 'Save the Google Drive client ID and secret first.');
        $state = Str::random(32);
        $r->session()->put('gdrive_state', $state);
        return redirect()->away(GoogleDrive::authUrl($state));
    }

    public function callback(Request $r)
    {
        if ($r->filled('error')) {
            return redirect()->route('admin.settings.index')->withErrors(['gdrive' => 'Google Drive was not connected: '.$r->input('error').'.']);
        }
        abort_unless($r->filled('code') && hash_equals((string) $r->session()->pull('gdrive_state'), (string) $r->input('state')), 403, 'This Google Drive link expired. Press Connect again.');
        try {
            GoogleDrive::finishConnect($r->input('code'));
        } catch (Throwable $e) {
            return redirect()->route('admin.settings.index')->withErrors(['gdrive' => $e->getMessage()]);
        }
        AuditService::log('settings.gdrive_connected');
        return redirect()->route('admin.settings.index')->with('status', 'Google Drive connected. New photos are saved to the “Daily Table photos” folder.');
    }

    public function disconnect()
    {
        GoogleDrive::disconnect();
        AuditService::log('settings.gdrive_disconnected');
        return back()->with('status', 'Google Drive disconnected. New photos are saved on this server.');
    }

    /** How many photos are still stored on this server. */
    public static function localPhotoCount(): int
    {
        $local = fn ($p) => $p && ! str_starts_with($p, 'http');
        return Dish::whereNotNull('image_path')->pluck('image_path')->filter($local)->count()
            + Banner::whereNotNull('image_path')->pluck('image_path')->filter($local)->count();
    }

    /** Copy dish and banner photos stored on this server to Google Drive, then point to the Drive copies. */
    public function migrate()
    {
        abort_unless(GoogleDrive::connected(), 422, 'Connect Google Drive first.');
        $moved = 0;
        $failed = 0;
        foreach ([[Dish::class, 'dishes'], [Banner::class, 'banners']] as [$model, $folder]) {
            foreach ($model::whereNotNull('image_path')->get() as $row) {
                $p = $row->image_path;
                if (str_starts_with($p, 'http')) {
                    continue;
                }
                $file = str_starts_with($p, 'uploads/') ? public_path($p) : Storage::disk('public')->path($p);
                if (! is_file($file)) {
                    continue;
                }
                try {
                    [, $url] = GoogleDrive::upload(new UploadedFile($file, basename($file), mime_content_type($file) ?: null, null, true), $folder);
                    $row->forceFill(['image_path' => $url])->save();
                    $moved++;
                } catch (Throwable) {
                    $failed++;
                }
            }
        }
        AuditService::log('settings.gdrive_migrated', null, ['moved' => $moved, 'failed' => $failed]);
        return back()->with('status', "Moved {$moved} photos to Google Drive".($failed ? ", {$failed} could not be moved (they still work from this server)." : '.').' The copies on this server are kept as a backup.');
    }
}
