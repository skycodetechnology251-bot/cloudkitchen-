<?php

namespace App\Services\Messaging;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push via Expo push service (works for Android + iOS builds made with Expo),
 * so no Firebase service account is needed on the server for the pilot.
 */
class PushService
{
    public function toUser(User $user, string $title, string $body, array $data = []): void
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();
        if (! $tokens) {
            return;
        }
        $messages = array_map(fn ($t) => ['to' => $t, 'title' => $title, 'body' => $body, 'data' => $data, 'sound' => 'default'], $tokens);
        if (! Setting::get('push', 'enabled', env('PUSH_ENABLED', false))) {
            Log::info('[Push] '.$title.' -> user '.$user->id, $data);
            return;
        }
        $req = Http::timeout(10)->acceptJson();
        if ($token = Setting::get('push', 'expo_access_token', env('EXPO_ACCESS_TOKEN'))) {
            $req = $req->withToken($token);
        }
        $req->post('https://exp.host/--/api/v2/push/send', $messages);
    }
}
