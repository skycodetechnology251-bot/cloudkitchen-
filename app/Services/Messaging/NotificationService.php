<?php

namespace App\Services\Messaging;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a topic message on each channel the customer allows.
 * Topics: order (always on for push while active), reminder, plan, offers, weekly, wallet.
 * Never includes health condition names in any message.
 */
class NotificationService
{
    private const DEFAULTS = [
        'order' => ['push' => true, 'whatsapp' => true, 'sms' => true, 'email' => true],
        'reminder' => ['push' => true, 'whatsapp' => false, 'sms' => false, 'email' => false],
        'wallet' => ['push' => true, 'whatsapp' => true, 'sms' => false, 'email' => false],
        'plan' => ['push' => true, 'whatsapp' => true, 'sms' => false, 'email' => true],
        'offers' => ['push' => false, 'whatsapp' => false, 'sms' => false, 'email' => false],
        'weekly' => ['push' => false, 'whatsapp' => false, 'sms' => false, 'email' => true],
    ];

    public function __construct(private PushService $push, private WhatsAppService $wa, private SmsGateway $sms) {}

    public function allowed(User $user, string $topic, string $channel): bool
    {
        if ($topic === 'order' && $channel === 'push') {
            return true;
        }
        $pref = NotificationPreference::where(['user_id' => $user->id, 'topic' => $topic, 'channel' => $channel])->first();
        return $pref ? $pref->enabled : (self::DEFAULTS[$topic][$channel] ?? false);
    }

    public function preferences(User $user): array
    {
        $out = self::DEFAULTS;
        foreach (NotificationPreference::where('user_id', $user->id)->get() as $p) {
            $out[$p->topic][$p->channel] = $p->enabled;
        }
        return $out;
    }

    public function notify(User $user, string $topic, string $title, string $body, array $opts = []): void
    {
        $channels = [
            'push' => fn () => $this->push->toUser($user, $title, $body, $opts['data'] ?? []),
            'whatsapp' => fn () => isset($opts['wa_template']) && $user->phone ? $this->wa->sendTemplate($user->phone, $opts['wa_template'], $opts['wa_params'] ?? []) : null,
            'sms' => fn () => isset($opts['sms_template']) && $user->phone ? $this->sms->send($user->phone, $opts['sms_template'], $opts['sms_vars'] ?? []) : null,
            'email' => fn () => $user->email ? Mail::raw($body, fn ($m) => $m->to($user->email)->subject($title)) : null,
        ];
        foreach ($channels as $channel => $send) {
            if (! $this->allowed($user, $topic, $channel)) {
                continue;
            }
            try {
                $send();
            } catch (Throwable $e) {
                Log::warning("[notify] {$channel} failed for user {$user->id}: ".$e->getMessage());
            }
        }
    }
}
