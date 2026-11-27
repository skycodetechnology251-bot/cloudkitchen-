<?php

namespace App\Console\Commands;

use App\Models\MealResponse;
use App\Models\Order;
use App\Models\User;
use App\Services\Messaging\NotificationService;
use Illuminate\Console\Command;

/** Sends one "Lunch today?" push per customer per day, skipping anyone who already ordered or answered. */
class LunchReminder extends Command
{
    protected $signature = 'dailytable:lunch-reminder';
    protected $description = 'Send the 10 AM lunch question';

    public function handle(NotificationService $notify): int
    {
        $today = now('Asia/Kolkata')->toDateString();
        $sent = 0;
        User::whereNull('role_id')->where('is_active', true)->whereHas('deviceTokens')->chunkById(500, function ($users) use ($notify, $today, &$sent) {
            foreach ($users as $u) {
                $answered = MealResponse::where(['user_id' => $u->id, 'meal' => 'lunch'])->whereDate('service_date', $today)->exists();
                $ordered = Order::where('user_id', $u->id)->whereDate('service_date', $today)->where('payment_status', 'paid')->exists();
                if ($answered || $ordered || ! $notify->allowed($u, 'reminder', 'push')) {
                    continue;
                }
                $notify->notify($u, 'reminder', 'Lunch today?', 'Tap to see today\'s menu. Nothing is ordered until you check out.', ['data' => ['screen' => 'Today', 'action' => 'lunch_question', 'date' => $today]]);
                $sent++;
            }
        });
        $this->info("Sent {$sent} reminders.");
        return self::SUCCESS;
    }
}
