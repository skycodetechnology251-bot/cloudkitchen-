<?php

return [
    'version' => '1.4.0',
    'brand' => env('APP_BRAND', 'Daily Table'),

    // Installation (browser wizard at /install, or php artisan dailytable:install)
    'installed' => env('DAILYTABLE_INSTALLED') === null ? null : filter_var(env('DAILYTABLE_INSTALLED'), FILTER_VALIDATE_BOOL),
    'install_lock' => 'app/private/installed.lock',
    'backups_path' => 'app/private/backups',
    // No cron job? Background tasks then run after page visits (at most once a minute).
    'web_cron' => filter_var(env('DAILYTABLE_WEB_CRON', true), FILTER_VALIDATE_BOOL),
    'requirements' => [
        'php' => '8.2.0',
        'extensions' => ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'mbstring', 'openssl', 'pdo_mysql', 'tokenizer', 'xml'],
        'writable' => ['storage', 'storage/app', 'storage/framework', 'storage/logs', 'bootstrap/cache'],
    ],
    'timezone' => 'Asia/Kolkata',
    'currency' => 'INR',

    'gst' => [
        'rate_percent' => (float) env('GST_RATE', 5),        // restaurant service: CGST 2.5 + SGST 2.5
        'prices_include_tax' => (bool) env('GST_INCLUSIVE', false),
        'sac' => env('GST_SAC', '996331'),
        'tax_delivery_fee' => true,
        'place_of_supply' => env('GST_PLACE_OF_SUPPLY', 'Tamil Nadu (33)'),
        'invoice_prefix' => env('INVOICE_PREFIX', 'DT'),
    ],

    'ordering' => [
        'slots' => ['lunch' => ['12:00-13:00', '13:00-14:00']],
        'default_cutoff' => '10:45',
        'reservation_minutes' => 10,
        'delivery_fee_paise' => (int) env('DELIVERY_FEE_PAISE', 2000),
        'free_cancel_until' => '10:45',
        'average_lunch_paise' => 14000,
    ],

    'reminder' => ['lunch_time' => '10:00'],

    'wallet' => [
        'presets_paise' => [50000, 100000, 200000, 300000],
        'min_topup_paise' => 10000,
        'max_balance_paise' => 2000000,
        'bonus_points_from_paise' => 200000,
        'bonus_points' => 50,
    ],

    'points' => [
        'earn_per_rupees' => 10,          // 1 point per Rs 10 of food
        'redeem_points' => 100,           // 100 points =
        'redeem_value_paise' => 2500,     // Rs 25
        'max_redeem_percent' => 20,
        'expiry_months' => 12,
        'bonus' => ['signup' => 50, 'first_order' => 100, 'referral' => 150, 'feedback' => 10],
    ],

    'otp' => [
        'length' => 6,
        'ttl_minutes' => 5,
        'resend_seconds' => 30,
        'max_per_hour' => 5,
        'max_attempts' => 5,
        'test_numbers' => array_filter(explode(',', (string) env('OTP_TEST_NUMBERS', ''))),
        'test_code' => env('OTP_TEST_CODE'),
    ],

    'delivery' => [
        'offer_seconds' => 45,
        'otp_length' => 4,
        'auto_detect_radius_m' => 150,
    ],
];
