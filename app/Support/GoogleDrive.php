<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Google Drive photo storage. Admin → Integrations → Google Drive: paste an OAuth client ID and secret
 * (Google Cloud → APIs & Services → Credentials → OAuth client, type "Web application", with the redirect
 * address shown on that page), then press "Connect Google Drive". Photos are uploaded to a "Daily Table photos"
 * folder in that Drive and shared as "anyone with the link can view" so the website and apps can show them.
 * Scope: drive.file (the website only sees files it created itself).
 */
class GoogleDrive
{
    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    public static function configured(): bool
    {
        return (bool) (Setting::get('gdrive', 'client_id') && Setting::get('gdrive', 'client_secret'));
    }

    public static function connected(): bool
    {
        return self::configured() && (bool) Setting::get('gdrive', 'refresh_token');
    }

    public static function redirectUri(): string
    {
        return route('admin.gdrive.callback');
    }

    public static function authUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => Setting::get('gdrive', 'client_id'), 'redirect_uri' => self::redirectUri(), 'response_type' => 'code',
            'scope' => self::SCOPE, 'access_type' => 'offline', 'prompt' => 'consent', 'include_granted_scopes' => 'true', 'state' => $state,
        ]);
    }

    /** Exchange the code from Google for a long-lived refresh token and create the photos folder. */
    public static function finishConnect(string $code): void
    {
        $res = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
            'code' => $code, 'client_id' => Setting::get('gdrive', 'client_id'), 'client_secret' => Setting::get('gdrive', 'client_secret'),
            'redirect_uri' => self::redirectUri(), 'grant_type' => 'authorization_code',
        ]);
        if (! $res->successful() || ! $res->json('refresh_token')) {
            throw new RuntimeException('Google did not return a refresh token: '.($res->json('error_description') ?? $res->json('error') ?? $res->status()));
        }
        Setting::put('gdrive', 'refresh_token', $res->json('refresh_token'), true);
        Cache::put('gdrive.token', $res->json('access_token'), now()->addSeconds(max(60, (int) $res->json('expires_in', 3600) - 120)));
        Setting::put('gdrive', 'folder_id', self::createFolder('Daily Table photos'));
        Setting::put('gdrive', 'connected_at', now()->toDateTimeString());
    }

    public static function disconnect(): void
    {
        Setting::put('gdrive', 'refresh_token', null, true);
        Setting::put('gdrive', 'folder_id', null);
        Setting::put('gdrive', 'connected_at', null);
        Cache::forget('gdrive.token');
    }

    private static function token(): string
    {
        return Cache::remember('gdrive.token', now()->addMinutes(50), function () {
            $res = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
                'client_id' => Setting::get('gdrive', 'client_id'), 'client_secret' => Setting::get('gdrive', 'client_secret'),
                'refresh_token' => Setting::get('gdrive', 'refresh_token'), 'grant_type' => 'refresh_token',
            ]);
            if (! $res->successful() || ! $res->json('access_token')) {
                throw new RuntimeException('Google Drive sign-in expired. Connect Google Drive again in Admin → Integrations.');
            }
            return $res->json('access_token');
        });
    }

    private static function createFolder(string $name, ?string $parent = null): string
    {
        $meta = ['name' => $name, 'mimeType' => 'application/vnd.google-apps.folder'] + ($parent ? ['parents' => [$parent]] : []);
        $res = Http::withToken(self::token())->timeout(30)->post('https://www.googleapis.com/drive/v3/files?fields=id', $meta);
        if (! $res->successful()) {
            throw new RuntimeException('Could not create the Google Drive folder ('.$res->status().').');
        }
        return $res->json('id');
    }

    /** Sub-folder (dishes, banners, branding…) inside the photos folder, created once. */
    private static function folder(string $sub): string
    {
        $root = Setting::get('gdrive', 'folder_id') ?: tap(self::createFolder('Daily Table photos'), fn ($id) => Setting::put('gdrive', 'folder_id', $id));
        $key = 'folder_'.preg_replace('/[^a-z0-9_]/', '', strtolower($sub));
        if ($id = Setting::get('gdrive', $key)) {
            return $id;
        }
        $id = self::createFolder(ucfirst($sub), $root);
        Setting::put('gdrive', $key, $id);
        return $id;
    }

    /** Upload a photo and share it for viewing. Returns [file id, public image address]. */
    public static function upload(UploadedFile $file, string $sub): array
    {
        $boundary = 'dt'.bin2hex(random_bytes(8));
        $meta = json_encode(['name' => $sub.'-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg'), 'parents' => [self::folder($sub)]]);
        $body = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$meta}\r\n"
            ."--{$boundary}\r\nContent-Type: ".($file->getMimeType() ?: 'image/jpeg')."\r\n\r\n".file_get_contents($file->getRealPath())."\r\n--{$boundary}--";
        $res = Http::withToken(self::token())->timeout(60)->withBody($body, 'multipart/related; boundary='.$boundary)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id');
        if (! $res->successful() || ! $res->json('id')) {
            throw new RuntimeException('Google Drive upload failed ('.$res->status().').');
        }
        $id = $res->json('id');
        Http::withToken(self::token())->timeout(30)->post("https://www.googleapis.com/drive/v3/files/{$id}/permissions", ['role' => 'reader', 'type' => 'anyone']);
        return [$id, self::url($id)];
    }

    public static function url(string $id): string
    {
        return 'https://lh3.googleusercontent.com/d/'.$id.'=w1600';
    }

    public static function idFromUrl(?string $url): ?string
    {
        return $url && preg_match('#^https://lh3\.googleusercontent\.com/d/([A-Za-z0-9_-]+)#', $url, $m) ? $m[1] : null;
    }

    public static function delete(string $id): void
    {
        try {
            Http::withToken(self::token())->timeout(20)->delete("https://www.googleapis.com/drive/v3/files/{$id}");
        } catch (\Throwable) {
            // Not critical: the old photo just stays in Drive.
        }
    }
}
