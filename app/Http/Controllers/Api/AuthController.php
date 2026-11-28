<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function requestOtp(Request $r, OtpService $otp)
    {
        $data = $r->validate(['phone' => ['required', 'regex:/^[6-9]\d{9}$/']]);
        $otp->send($data['phone'], $r->ip());
        return response()->json(['sent' => true, 'resend_after' => config('dailytable.otp.resend_seconds')]);
    }

    public function verifyOtp(Request $r, OtpService $otp, PointsService $points)
    {
        $data = $r->validate([
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'code' => ['required', 'digits:'.config('dailytable.otp.length')],
            'device' => ['nullable', 'string', 'max:60'],
            'google_link_token' => ['nullable', 'string'],
        ]);
        $otp->verify($data['phone'], $data['code']);

        $user = User::where('phone', $data['phone'])->first();
        $isNew = false;
        if (! $user && $r->filled('google_link_token')) {
            // Google sign-up continues here: attach the verified phone to the Google account.
            $user = User::where('google_id', decrypt($data['google_link_token']))->first();
        }
        if (! $user) {
            $user = User::create(['name' => 'New customer', 'phone' => $data['phone']]);
            $isNew = true;
        }
        $user->forceFill(['phone' => $data['phone'], 'phone_verified_at' => now(), 'last_seen_at' => now()])->save();
        if (! $user->is_active) {
            throw ValidationException::withMessages(['phone' => 'This account is disabled. Contact support.']);
        }
        if ($isNew) {
            $user->walletOrCreate();
            $points->award($user, config('dailytable.points.bonus.signup'), 'signup');
        }
        return $this->session($user, $data['device'] ?? 'app', $isNew);
    }

    public function google(Request $r, GoogleAuthService $google)
    {
        $data = $r->validate(['id_token' => 'required|string', 'device' => 'nullable|string|max:60']);
        $claims = $google->verify($data['id_token']);
        $user = User::where('google_id', $claims['sub'])->first()
            ?? User::where('email', $claims['email'])->first();
        if ($user) {
            $user->forceFill(['google_id' => $claims['sub'], 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        } else {
            $user = User::create(['name' => $claims['name'] ?? 'New customer', 'email' => $claims['email']]);
            $user->forceFill(['google_id' => $claims['sub'], 'email_verified_at' => now()])->save();
        }
        if (! $user->phone_verified_at) {
            // Mobile number is required for delivery: app must finish with OTP.
            return response()->json(['needs_phone' => true, 'google_link_token' => encrypt($claims['sub']), 'name' => $user->name]);
        }
        return $this->session($user, $data['device'] ?? 'app', false);
    }

    /** New customer with email + password (used when SMS/OTP is not switched on). */
    public function emailRegister(Request $r, PointsService $points)
    {
        $data = $r->validate([
            'name' => 'required|string|min:2|max:80',
            'email' => 'required|email|max:120',
            'phone' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'password' => 'required|string|min:8|max:100|confirmed',
            'device' => ['nullable', 'string', 'max:60'],
        ], [
            'phone.regex' => 'Enter your 10-digit mobile number (for delivery).',
            'password.min' => 'Use at least 8 characters for your password.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);
        $email = strtolower(trim($data['email']));
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'This email already has an account. Please log in.']);
        }
        if (User::where('phone', $data['phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'This mobile number already has an account. Log in with its email, or contact support.']);
        }
        $user = User::create(['name' => trim($data['name']), 'email' => $email, 'phone' => $data['phone']]);
        $user->forceFill(['password' => $data['password'], 'last_seen_at' => now()])->save();
        $user->walletOrCreate();
        $points->award($user, config('dailytable.points.bonus.signup'), 'signup');

        return $this->session($user, $data['device'] ?? 'app', true);
    }

    public function emailLogin(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string', 'device' => ['nullable', 'string', 'max:60']]);
        $user = User::where('email', strtolower(trim($data['email'])))->first();
        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Wrong email or password.']);
        }
        if ($user->isStaff()) {
            throw ValidationException::withMessages(['email' => 'This is a staff account. Please use the staff login on the website.']);
        }
        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'This account is disabled. Contact support.']);
        }
        $user->forceFill(['last_seen_at' => now()])->save();

        return $this->session($user, $data['device'] ?? 'app', false);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();
        return response()->json(['ok' => true]);
    }

    private function session(User $user, string $device, bool $isNew)
    {
        $abilities = $user->isStaff() ? ['staff', 'role:'.$user->role->slug] : ['customer'];
        $token = $user->createToken($device, $abilities, now()->addDays(60))->plainTextToken;
        return response()->json(['token' => $token, 'is_new' => $isNew, 'user' => (new MeController)->payload($user)]);
    }
}
