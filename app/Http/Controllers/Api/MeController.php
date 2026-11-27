<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Messaging\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeController extends Controller
{
    public function payload(User $user): array
    {
        $user->loadMissing('role', 'healthProfile', 'wallet');
        $w = $user->walletOrCreate();
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'date_of_birth' => $user->date_of_birth?->toDateString(),
            'ordering_for' => $user->ordering_for,
            'role' => $user->role?->slug ?? 'customer',
            'locale' => $user->locale,
            'wallet' => ['balance_paise' => $w->balance_paise, 'low' => $w->isLow()],
            'points' => $user->pointsBalance(),
            'has_health_profile' => (bool) $user->healthProfile,
            'coach_on' => (bool) $user->coach_consent_at,
        ];
    }

    public function show(Request $r)
    {
        return response()->json($this->payload($r->user()));
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'name' => 'sometimes|string|min:2|max:80',
            'email' => ['sometimes', 'nullable', 'email', Rule::unique('users')->ignore($r->user()->id)],
            'date_of_birth' => 'sometimes|nullable|date|before:today',
            'locale' => 'sometimes|in:en,ta',
            'ordering_for' => 'sometimes|nullable|in:self,family',
        ]);
        $r->user()->update($data);
        return response()->json($this->payload($r->user()->fresh()));
    }

    public function health(Request $r)
    {
        $p = $r->user()->healthProfile;
        return response()->json($p ? $p->only(['diet', 'allergies', 'height_cm', 'weight_kg', 'spice', 'dislikes', 'activity']) + [
            'conditions' => $p->conditions_consent_at ? ($p->conditions ?? []) : [],
            'conditions_consent' => (bool) $p->conditions_consent_at,
            'bmi' => $p->bmi(),
        ] : null);
    }

    public function saveHealth(Request $r)
    {
        $data = $r->validate([
            'diet' => 'nullable|in:veg,egg,nonveg',
            'allergies' => 'nullable|array', 'allergies.*' => 'string|max:30',
            'conditions_consent' => 'boolean',
            'conditions' => 'nullable|array', 'conditions.*' => 'string|max:40',
            'height_cm' => 'nullable|numeric|between:80,250',
            'weight_kg' => 'nullable|numeric|between:20,300',
            'spice' => 'nullable|in:mild,medium,spicy',
            'dislikes' => 'nullable|string|max:200',
            'activity' => 'nullable|string|max:60',
        ]);
        $user = $r->user();
        $consent = (bool) ($data['conditions_consent'] ?? false);
        $profile = $user->healthProfile()->firstOrNew();
        $profile->fill(collect($data)->except(['conditions', 'conditions_consent'])->all());
        if ($consent) {
            $profile->conditions = $data['conditions'] ?? [];
            $profile->conditions_consent_at ??= now();
            $profile->consent_version = '1.0';
            Consent::firstOrCreate(['user_id' => $user->id, 'purpose' => 'health_suggestions', 'withdrawn_at' => null], ['policy_version' => '1.0', 'granted_at' => now()]);
        } else {
            // Withdrawal: stop using and delete condition data.
            $profile->conditions = null;
            $profile->conditions_consent_at = null;
            Consent::where(['user_id' => $user->id, 'purpose' => 'health_suggestions'])->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
        }
        $profile->user_id = $user->id;
        $profile->save();
        return $this->health($r);
    }

    public function notificationPrefs(Request $r, NotificationService $n)
    {
        return response()->json($n->preferences($r->user()));
    }

    public function saveNotificationPrefs(Request $r)
    {
        $data = $r->validate(['prefs' => 'required|array']);
        foreach ($data['prefs'] as $topic => $channels) {
            foreach ((array) $channels as $channel => $enabled) {
                if (! in_array($channel, ['push', 'whatsapp', 'sms', 'email'], true)) {
                    continue;
                }
                NotificationPreference::updateOrCreate(['user_id' => $r->user()->id, 'topic' => $topic, 'channel' => $channel], ['enabled' => (bool) $enabled]);
                if ($topic === 'offers') {
                    $purpose = 'marketing_'.$channel;
                    $enabled
                        ? Consent::firstOrCreate(['user_id' => $r->user()->id, 'purpose' => $purpose, 'withdrawn_at' => null], ['policy_version' => '1.0', 'granted_at' => now()])
                        : Consent::where(['user_id' => $r->user()->id, 'purpose' => $purpose])->whereNull('withdrawn_at')->update(['withdrawn_at' => now()]);
                }
            }
        }
        return $this->notificationPrefs($r, app(NotificationService::class));
    }

    public function registerDevice(Request $r)
    {
        $data = $r->validate(['token' => 'required|string|max:255', 'platform' => 'required|in:android,ios,web']);
        $r->user()->deviceTokens()->updateOrCreate(['token' => $data['token']], ['platform' => $data['platform'], 'last_used_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function deleteAccount(Request $r)
    {
        $u = $r->user();
        $u->tokens()->delete();
        $u->healthProfile()?->delete();
        $u->wellnessDays()->delete();
        $u->forceFill(['name' => 'Deleted user', 'email' => null, 'google_id' => null, 'is_active' => false])->save();
        $u->delete(); // soft delete keeps invoices and payments for legal retention
        return response()->json(['ok' => true]);
    }
}
