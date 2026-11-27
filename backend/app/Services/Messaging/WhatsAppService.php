<?php

namespace App\Services\Messaging;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** WhatsApp Business Platform (Cloud API) template messages. */
class WhatsAppService
{
    public function sendTemplate(string $phone, string $template, array $params = [], string $lang = 'en'): void
    {
        $phoneId = Setting::get('whatsapp', 'phone_number_id', env('WHATSAPP_PHONE_NUMBER_ID'));
        $token = Setting::get('whatsapp', 'access_token', env('WHATSAPP_TOKEN'));
        if (! $phoneId || ! $token) {
            Log::info('[WhatsApp] '.$template.' to '.$phone, $params);
            return;
        }
        Http::withToken($token)->timeout(10)->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => '91'.ltrim($phone, '+91'),
            'type' => 'template',
            'template' => [
                'name' => $template,
                'language' => ['code' => $lang],
                'components' => $params ? [['type' => 'body', 'parameters' => array_map(fn ($p) => ['type' => 'text', 'text' => (string) $p], $params)]] : [],
            ],
        ])->throw();
    }
}
