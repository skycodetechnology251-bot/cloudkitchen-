<?php

use App\Models\Wallet;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Schedule;

// Release capacity held by unpaid orders.
Schedule::call(fn () => app(OrderService::class)->releaseExpired())->everyMinute()->name('release-expired')->withoutOverlapping();

// Low-wallet reminders at 9:30 AM, before the 10 AM lunch question.
Schedule::call(function () {
    Wallet::whereColumn('balance_paise', '<', 'low_balance_paise')->with('user')->each(fn ($w) => app(WalletService::class)->maybeLowBalanceAlert($w));
})->dailyAt('09:30')->timezone('Asia/Kolkata')->name('wallet-low');

// Daily "Lunch today?" reminder.
Schedule::command('dailytable:lunch-reminder')->dailyAt('10:00')->timezone('Asia/Kolkata');

// Heartbeat: tells the site a real cron job is running, so page visits don't also run the scheduler.
Schedule::call(function () {
    if (! app()->bound('webcron.running')) {
        \Illuminate\Support\Facades\Cache::put('cron.heartbeat', now(), 300);
    }
})->everyMinute()->name('cron-heartbeat');
