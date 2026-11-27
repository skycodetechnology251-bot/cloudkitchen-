<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\CoachService;
use Illuminate\Http\Request;

/** Wellness coach for customers: water and workout logs (only with consent) and daily advice. */
class CoachController extends Controller
{
    public function status(Request $r)
    {
        return response()->json(['on' => (bool) $r->user()->coach_consent_at, 'since' => $r->user()->coach_consent_at, 'workout_types' => CoachService::WORKOUT_TYPES]);
    }

    /** Turn the coach on or off. Off deletes every water and workout day we stored. */
    public function consent(Request $r)
    {
        $on = $r->validate(['on' => 'required|boolean'])['on'];
        $u = $r->user();
        $u->forceFill(['coach_consent_at' => $on ? ($u->coach_consent_at ?? now()) : null])->save();
        if (! $on) {
            $u->wellnessDays()->delete();
        }
        AuditService::log($on ? 'coach.consent_on' : 'coach.consent_off', $u);
        return response()->json(['on' => (bool) $u->coach_consent_at]);
    }

    public function sync(Request $r, CoachService $coach)
    {
        abort_unless($r->user()->coach_consent_at, 403, 'Turn on the wellness coach first.');
        $d = $r->validate([
            'days' => 'required|array|max:14',
            'days.*.date' => 'required|date_format:Y-m-d',
            'days.*.glasses' => 'nullable|integer|min:0|max:40',
            'days.*.glass_goal' => 'nullable|integer|min:4|max:16',
            'days.*.workout_goal' => 'nullable|integer|min:5|max:180',
            'days.*.workouts' => 'nullable|array|max:20',
            'days.*.workouts.*.type' => 'required|string|max:20',
            'days.*.workouts.*.minutes' => 'required|integer|min:1|max:300',
            'days.*.workouts.*.intensity' => 'nullable|in:easy,moderate,hard',
        ]);
        $today = now(config('dailytable.timezone'))->toDateString();
        $days = array_values(array_filter($d['days'], fn ($x) => $x['date'] <= $today));
        $coach->sync($r->user(), $days);
        return response()->json(['ok' => true, 'saved' => count($days)]);
    }

    public function foods(Request $r, CoachService $coach)
    {
        $d = $r->validate(['kitchen_id' => 'required|integer|exists:kitchens,id']);
        return response()->json($coach->foodsOnly($r->user(), (int) $d['kitchen_id']));
    }

    public function show(Request $r, CoachService $coach)
    {
        abort_unless($r->user()->coach_consent_at, 403, 'Turn on the wellness coach first.');
        $d = $r->validate(['kitchen_id' => 'nullable|integer|exists:kitchens,id']);
        return response()->json($coach->build($r->user(), $d['kitchen_id'] ?? null));
    }
}
