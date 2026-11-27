<?php

namespace App\Services\Messaging;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * DLT-compliant SMS. Driver "log" writes to the log (local/dev); "http" posts to a
 * provider endpoint configured in Admin > Integrations > SMS (MSG91-style JSON API).
 */
class SmsGateway
{
    public function sendOtp(string $phone, string $code): void
    {
        $this->send($phone, Setting::get('sms', 'otp_template_id', env('SMS_OTP_TEMPLATE_ID')), ['otp' => $code]);
    }

    public function send(string $phone, ?string $templateId, array $vars): void
    {
        $driver = Setting::get('sms', 'driver', env('SMS_DRIVER', 'log'));
        if ($driver === 'log') {
            Log::info('[SMS] to '.$phone.' template '.$templateId, $vars);
            return;
        }
        Http::timeout(10)->withHeaders(['authkey' => Setting::get('sms', 'api_key', env('SMS_API_KEY'))])
            ->post(Setting::get('sms', 'endpoint', env('SMS_ENDPOINT')), [
                'template_id' => $templateId,
                'sender' => Setting::get('sms', 'sender_id', env('SMS_SENDER_ID', 'DLYTBL')),
                'recipients' => [array_merge(['mobiles' => '91'.ltrim($phone, '+91')], $vars)],
            ])->throw();
    }
}
