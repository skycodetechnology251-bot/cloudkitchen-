<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\Request;

/** Integrations: secrets are encrypted at rest and never shown back in full. */
class SettingController extends Controller
{
    public const GROUPS = [
        'razorpay' => ['label' => 'Razorpay payments', 'fields' => ['key_id' => ['Key ID', false], 'key_secret' => ['Key secret', true], 'webhook_secret' => ['Webhook secret', true]],
            'note' => 'Webhook URL: /api/v1/webhooks/razorpay. Events: payment.captured, payment.failed, refund.processed.'],
        'google' => ['label' => 'Google sign-in', 'fields' => ['android_client_id' => ['Android client ID', false], 'ios_client_id' => ['iPhone client ID', false], 'web_client_id' => ['Web client ID', false], 'client_ids' => ['Other allowed client IDs (optional, comma separated)', false]],
            'note' => 'Used by the website and both apps (written to mobile/app-config.json). Customers still verify a mobile number after Google sign-up.'],
        'sms' => ['label' => 'SMS & OTP', 'fields' => ['driver' => ['Driver (log or http)', false], 'endpoint' => ['Provider API endpoint', false], 'api_key' => ['API key', true], 'sender_id' => ['DLT sender ID', false], 'otp_template_id' => ['DLT OTP template ID', false]],
            'note' => 'Use "log" during setup: codes are written to storage/logs/laravel.log.'],
        'push' => ['label' => 'Push notifications', 'fields' => ['enabled' => ['Enabled (1 or 0)', false], 'expo_access_token' => ['Expo access token (optional)', true]],
            'note' => 'Apps register their Expo push token on login.'],
        'whatsapp' => ['label' => 'WhatsApp Business', 'fields' => ['phone_number_id' => ['Phone number ID', false], 'access_token' => ['Access token', true]],
            'note' => 'Templates used: order_confirmed, wallet_low, lunch_reminder, offer_weekly. Get them approved in Meta Business Manager.'],
        'mail' => ['label' => 'Email', 'fields' => ['from_address' => ['From address', false]],
            'note' => 'SMTP is set in .env (MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD).'],
        'seo' => ['label' => 'SEO', 'fields' => ['site_title' => ['Site title', false], 'meta_description' => ['Meta description', false]],
            'note' => 'Each live zone gets its own area page and sitemap entry automatically.'],
        'app' => ['label' => 'App links & support', 'fields' => ['android_url' => ['Android download link', false], 'ios_url' => ['iPhone App Store link', false], 'support_email' => ['Support email', false]], 'note' => 'Shown on the website and in the apps.'],
        'mobile' => ['label' => 'Mobile apps', 'fields' => [
            'customer_name' => ['Customer app name', false], 'customer_package' => ['Customer app Android package (e.g. in.dailytable.app)', false],
            'rider_name' => ['Rider app name', false], 'rider_package' => ['Rider app Android package (e.g. in.dailytable.rider)', false],
            'version' => ['App version (e.g. 1.0.0)', false], 'support_phone' => ['Support phone (Call button in the apps)', false],
            'support_whatsapp' => ['Support WhatsApp number (with country code, e.g. 919840012345)', false], 'eas_project_id' => ['Expo project ID (optional, for push and iPhone builds)', false]],
            'note' => 'The apps use the same settings as this website: the website address, these values and Google sign-in are written to mobile/app-config.json and served at /api/v1/app-config, which the app build on GitHub reads. Changing an app name, package or version needs a new APK build; support details update in the apps straight away.'],
        'openai' => ['label' => 'OpenAI', 'fields' => ['api_key' => ['API key', true], 'model' => ['Model', false], 'monthly_budget_inr' => ['Monthly budget (₹)', false]],
            'note' => 'Names and phone numbers are removed before text is sent. AI never gives medical advice.'],
    ];

    public function index()
    {
        $values = [];
        foreach (self::GROUPS as $g => $cfg) {
            foreach ($cfg['fields'] as $k => [$label, $secret]) {
                $v = Setting::get($g, $k);
                $values[$g][$k] = $secret ? ($v ? '•••• stored' : '') : $v;
            }
        }
        return view('admin.settings', ['groups' => self::GROUPS, 'values' => $values, 'gst' => config('dailytable.gst')]);
    }

    public function update(Request $r, string $group)
    {
        abort_unless(isset(self::GROUPS[$group]), 404);
        foreach (self::GROUPS[$group]['fields'] as $k => [$label, $secret]) {
            $v = $r->input($k);
            if ($secret && ($v === null || $v === '' || str_starts_with($v, '••••'))) {
                continue; // keep the stored secret
            }
            Setting::put($group, $k, $v, $secret);
        }
        AuditService::log('settings.update', null, ['group' => $group]);
        if (in_array($group, ['google', 'mobile', 'app'], true)) {
            \App\Support\MobileConfig::write();
        }
        return back()->with('status', self::GROUPS[$group]['label'].' saved.');
    }
}
