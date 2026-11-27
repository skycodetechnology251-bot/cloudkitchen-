<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/** Verifies a Google ID token from the app and returns its verified claims. */
class GoogleAuthService
{
    public function verify(string $idToken): array
    {
        $res = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        if (! $res->ok()) {
            throw ValidationException::withMessages(['id_token' => 'Google sign-in failed. Please try again.']);
        }
        $claims = $res->json();
        $allowed = array_filter(array_map('trim', explode(',', (string) Setting::get('google', 'client_ids', env('GOOGLE_CLIENT_IDS', '')))));
        if (! in_array($claims['aud'] ?? '', $allowed, true)) {
            throw ValidationException::withMessages(['id_token' => 'This Google sign-in is not allowed for Daily Table.']);
        }
        if (($claims['email_verified'] ?? 'false') !== 'true' && ($claims['email_verified'] ?? false) !== true) {
            throw ValidationException::withMessages(['id_token' => 'Your Google email is not verified.']);
        }
        return $claims;
    }
}
