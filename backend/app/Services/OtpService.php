<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Services\Messaging\SmsGateway;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    public function __construct(private SmsGateway $sms) {}

    public function send(string $phone, ?string $ip = null): void
    {
        $cfg = config('dailytable.otp');
        $key = 'otp:'.$phone;
        if (RateLimiter::tooManyAttempts($key, $cfg['max_per_hour'])) {
            throw ValidationException::withMessages(['phone' => 'Too many codes requested. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes.']);
        }
        $last = OtpCode::where('phone', $phone)->latest('id')->first();
        if ($last && $last->created_at->diffInSeconds(now()) < $cfg['resend_seconds']) {
            throw ValidationException::withMessages(['phone' => 'Please wait before asking for a new code.']);
        }
        RateLimiter::hit($key, 3600);

        $isTest = in_array($phone, $cfg['test_numbers'], true) && $cfg['test_code'];
        $code = $isTest ? (string) $cfg['test_code'] : str_pad((string) random_int(0, 10 ** $cfg['length'] - 1), $cfg['length'], '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($cfg['ttl_minutes']),
            'ip_address' => $ip,
        ]);

        if (! $isTest) {
            $this->sms->sendOtp($phone, $code);
        }
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone', $phone)->whereNull('consumed_at')->latest('id')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'This code has expired. Ask for a new one.']);
        }
        if ($otp->attempts >= config('dailytable.otp.max_attempts')) {
            throw ValidationException::withMessages(['code' => 'Too many wrong attempts. Ask for a new code.']);
        }
        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw ValidationException::withMessages(['code' => 'Wrong code. Check the SMS and try again.']);
        }
        $otp->update(['consumed_at' => now()]);
        return true;
    }
}
