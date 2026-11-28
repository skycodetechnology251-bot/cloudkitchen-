<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * One place to save photos (dishes, banners, logo, app login pictures).
 * Google Drive when it is connected (Admin → Integrations); otherwise public/uploads on this server.
 * What is stored in the database is either a full https address (Drive) or a path like uploads/dishes/x.jpg.
 */
class Media
{
    public static function store(UploadedFile $file, string $folder, ?string $replace = null): string
    {
        if (GoogleDrive::connected()) {
            try {
                [, $url] = GoogleDrive::upload($file, $folder);
                self::delete($replace);
                return $url;
            } catch (Throwable $e) {
                Log::warning('Google Drive upload failed, saved on the server instead: '.$e->getMessage());
            }
        }
        $dir = 'uploads/'.trim($folder, '/');
        @mkdir(public_path($dir), 0755, true);
        $name = Str::lower(Str::random(16)).'.'.strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $file->move(public_path($dir), $name);
        self::delete($replace);
        return $dir.'/'.$name;
    }

    /** Address for an <img> tag, from whatever was stored. */
    public static function url(?string $stored): ?string
    {
        if (! $stored) {
            return null;
        }
        if (str_starts_with($stored, 'http://') || str_starts_with($stored, 'https://')) {
            return $stored;
        }
        if (str_starts_with($stored, 'uploads/')) {
            return asset($stored);
        }
        return Storage::disk('public')->url($stored); // older uploads (storage/app/public)
    }

    public static function delete(?string $stored): void
    {
        if (! $stored) {
            return;
        }
        if ($id = GoogleDrive::idFromUrl($stored)) {
            GoogleDrive::delete($id);
        } elseif (str_starts_with($stored, 'uploads/') && is_file(public_path($stored))) {
            @unlink(public_path($stored));
        }
    }
}
