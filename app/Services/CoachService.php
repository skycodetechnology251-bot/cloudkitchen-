<?php

namespace App\Services;

use App\Models\DailyMenu;
use App\Models\User;
use App\Models\WellnessDay;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Wellness coach: reads the customer's water and workout days (only with consent), spots patterns,
 * nudges them to drink more when they fall behind, motivates daily workouts and picks dishes from
 * the menu for before / after a workout and for hydration.
 *
 * Rules decide everything (what to say, which dishes are allowed). AI, when an OpenAI key is set, only
 * rewrites the result as a short friendly message. No name, phone, health condition or address is sent.
 * Never medical advice.
 */
class CoachService
{
    /** Ingredients and dish names that add water to the day. */
    private const HYDRATING = ['buttermilk', 'mor', 'curd', 'yogurt', 'yoghurt', 'raita', 'fruit', 'cucumber', 'melon', 'coconut', 'lemon', 'rasam', 'neer', 'lassi', 'tender', 'orange', 'papaya', 'kootu'];

    public const WORKOUT_TYPES = ['walk' => 'Walk', 'run' => 'Run', 'yoga' => 'Yoga', 'gym' => 'Gym', 'cycling' => 'Cycling', 'home' => 'Home workout', 'dance' => 'Dance', 'sports' => 'Sports', 'swim' => 'Swimming'];

    public function __construct(private AiService $ai) {}

    /** Save the days the app sends (last 14 at most). */
    public function sync(User $user, array $days): void
    {
        $tz = config('dailytable.timezone');
        $oldest = now($tz)->subDays(30)->toDateString();
        foreach ($days as $d) {
            if (($d['date'] ?? '') < $oldest) {
                continue;
            }
            $workouts = collect($d['workouts'] ?? [])->take(20)->map(fn ($w) => [
                'type' => array_key_exists($w['type'] ?? '', self::WORKOUT_TYPES) ? $w['type'] : 'home',
                'minutes' => max(1, min(300, (int) ($w['minutes'] ?? 0))),
                'intensity' => in_array($w['intensity'] ?? '', ['easy', 'moderate', 'hard'], true) ? $w['intensity'] : 'moderate',
            ])->values()->all();
            WellnessDay::updateOrCreate(['user_id' => $user->id, 'day' => $d['date']], [
                'glasses' => max(0, min(40, (int) ($d['glasses'] ?? 0))),
                'glass_goal' => max(4, min(16, (int) ($d['glass_goal'] ?? 8))),
                'workout_minutes' => min(600, collect($workouts)->sum('minutes')),
                'workout_goal' => max(5, min(180, (int) ($d['workout_goal'] ?? 30))),
                'workouts' => $workouts,
            ]);
        }
        // Keep only the last 60 days.
        WellnessDay::where('user_id', $user->id)->where('day', '<', now($tz)->subDays(60)->toDateString())->delete();
        Cache::forget('coach.'.$user->id);
    }

    public function build(User $user, ?int $kitchenId): array
    {
        $tz = config('dailytable.timezone');
        $now = now($tz);
        $days = WellnessDay::where('user_id', $user->id)->where('day', '>=', $now->copy()->subDays(13)->toDateString())->orderBy('day')->get()
            ->keyBy(fn ($d) => $d->day->toDateString());
        $today = $days->get($now->toDateString());

        $water = $this->water($days, $today, $now);
        $workout = $this->workout($days, $today, $now);
        $foods = $kitchenId ? $this->foods($user, $kitchenId, $water, $workout) : ['date' => null, 'before' => [], 'after' => [], 'hydrate' => [], 'salty' => []];

        $insights = array_values(array_filter([$water['insight'], $workout['insight'], $this->foodInsight($foods, $water, $workout)]));
        $fallback = collect($insights)->pluck('text')->take(2)->implode(' ');
        $message = $this->aiMessage($user, $water, $workout, $foods) ?? $fallback;

        return [
            'water' => collect($water)->except('insight')->all(),
            'workout' => collect($workout)->except('insight')->all(),
            'insights' => $insights,
            'foods' => $foods,
            'message' => $message,
            'ai' => $message !== $fallback,
            'disclaimer' => 'Tips for everyday wellbeing, not medical advice. If you have a health condition, follow your doctor.',
        ];
    }

    private function water(Collection $days, $today, Carbon $now): array
    {
        $goal = (int) ($today->glass_goal ?? $days->last()->glass_goal ?? 8);
        $glasses = (int) ($today->glasses ?? 0);
        $past = $days->filter(fn ($d) => $d->day->toDateString() < $now->toDateString())->take(-7);
        $avg = $past->count() ? round($past->avg('glasses'), 1) : null;
        $metDays = $past->filter(fn ($d) => $d->glasses >= $d->glass_goal)->count();
        // How much should be done by now, if spread from 8 AM to 8 PM.
        $frac = max(0, min(1, ($now->hour * 60 + $now->minute - 8 * 60) / (12 * 60)));
        $expected = (int) floor($goal * $frac);
        $behind = max(0, $expected - $glasses);

        if ($glasses >= $goal) {
            $insight = ['kind' => 'water', 'tone' => 'good', 'icon' => '💧', 'title' => 'Water goal reached', 'text' => "You've had {$glasses} glasses today. Great job keeping hydrated!"];
        } elseif ($behind >= 2) {
            $insight = ['kind' => 'water', 'tone' => 'warn', 'icon' => '💧', 'title' => 'Time to catch up on water', 'text' => "You're at {$glasses} of {$goal} glasses; by now most days you'd be near {$expected}. Drink ".min($behind, 2).' glasses in the next hour.'];
        } elseif ($avg !== null && $avg < $goal * 0.7) {
            $insight = ['kind' => 'water', 'tone' => 'warn', 'icon' => '💧', 'title' => 'You often drink less than your goal', 'text' => "Last week you averaged {$avg} glasses a day against a goal of {$goal}. Keep a bottle nearby and add a glass with every meal."];
        } elseif ($avg !== null && $metDays >= 5) {
            $insight = ['kind' => 'water', 'tone' => 'good', 'icon' => '💧', 'title' => 'Great hydration habit', 'text' => "You met your water goal on {$metDays} of the last 7 days. Keep it going!"];
        } else {
            $left = max(0, $goal - $glasses);
            $insight = ['kind' => 'water', 'tone' => 'info', 'icon' => '💧', 'title' => 'Water today', 'text' => "{$glasses} of {$goal} glasses so far. {$left} more to go, spread through the day."];
        }

        return ['glasses' => $glasses, 'goal' => $goal, 'expected_now' => $expected, 'behind' => $behind, 'avg7' => $avg, 'met_days7' => $metDays, 'insight' => $insight];
    }

    private function workout(Collection $days, $today, Carbon $now): array
    {
        $goal = (int) ($today->workout_goal ?? $days->last()->workout_goal ?? 30);
        $minutes = (int) ($today->workout_minutes ?? 0);
        $week = $days->filter(fn ($d) => $d->day->gte($now->copy()->subDays(6)->startOfDay()))->sum('workout_minutes');
        // Streak: consecutive days with 10+ minutes, ending today (or yesterday if not done yet today).
        $streak = 0;
        $cursor = $minutes >= 10 ? $now->copy() : $now->copy()->subDay();
        while (($d = $days->get($cursor->toDateString())) && $d->workout_minutes >= 10) {
            $streak++;
            $cursor->subDay();
        }
        $last = collect($today->workouts ?? [])->last();

        if ($minutes >= $goal) {
            $insight = ['kind' => 'workout', 'tone' => 'good', 'icon' => '🏃', 'title' => $streak > 1 ? "{$streak}-day workout streak! 🔥" : 'Workout done today', 'text' => "{$minutes} minutes today. Refuel with protein and drink 1–2 extra glasses of water."];
        } elseif ($minutes > 0) {
            $insight = ['kind' => 'workout', 'tone' => 'info', 'icon' => '🏃', 'title' => 'Nice start', 'text' => "{$minutes} of {$goal} minutes done. A ".max(10, $goal - $minutes).'-minute walk after lunch finishes your goal.'];
        } elseif ($streak > 0) {
            $insight = ['kind' => 'workout', 'tone' => 'warn', 'icon' => '🔥', 'title' => "Keep your {$streak}-day streak alive", 'text' => 'Even 15 minutes today keeps it going. A brisk walk counts!'];
        } elseif ($week < 60) {
            $insight = ['kind' => 'workout', 'tone' => 'warn', 'icon' => '🏃', 'title' => 'Let’s get moving', 'text' => 'Only '.$week.' active minutes this week. Start small: 10 minutes of walking or stretching today.'];
        } else {
            $insight = ['kind' => 'workout', 'tone' => 'info', 'icon' => '🏃', 'title' => 'Workout today?', 'text' => "{$week} active minutes this week. Aim for {$goal} minutes today — about 150 minutes a week is a good everyday target."];
        }

        return ['minutes' => $minutes, 'goal' => $goal, 'week_minutes' => $week, 'streak' => $streak, 'done_today' => $minutes >= $goal, 'last' => $last, 'insight' => $insight];
    }

    /** Workout food picks without any logs (no consent needed: uses only diet and allergies). */
    public function foodsOnly(User $user, int $kitchenId): array
    {
        return $this->foods($user, $kitchenId, ['behind' => 0, 'avg7' => null, 'goal' => 8], ['minutes' => 0, 'done_today' => false]);
    }

    /** Dishes from the next orderable menu that respect the customer's diet and allergies. */
    private function foods(User $user, int $kitchenId, array $water, array $workout): array
    {
        $tz = config('dailytable.timezone');
        $date = DailyMenu::where('kitchen_id', $kitchenId)->where('meal', 'lunch')->whereDate('service_date', '>=', now($tz)->toDateString())->orderBy('service_date')->value('service_date');
        if (! $date) {
            return ['date' => null, 'before' => [], 'after' => [], 'hydrate' => [], 'salty' => []];
        }
        $profile = $user->healthProfile;
        $allergies = array_map('strtolower', $profile?->allergies ?? []);
        $items = DailyMenu::with('dish')->where('kitchen_id', $kitchenId)->whereDate('service_date', $date)->where('status', 'open')->get()
            ->filter(fn ($m) => $m->dish && $m->dish->is_active && $m->available() > 0)
            ->filter(fn ($m) => ! ($profile?->diet === 'veg' && $m->dish->diet !== 'veg') && ! ($profile?->diet === 'egg' && $m->dish->diet === 'nonveg'))
            ->filter(fn ($m) => ! array_intersect($allergies, array_map('strtolower', $m->dish->allergens ?? [])))
            ->values();

        $card = fn ($m, string $why) => ['daily_menu_id' => $m->id, 'slug' => $m->dish->slug, 'name' => $m->dish->name, 'kcal' => $m->dish->kcal, 'protein_g' => $m->dish->protein_g,
            'carbs_g' => $m->dish->carbs_g, 'price_paise' => $m->price_paise, 'diet' => $m->dish->diet, 'image_url' => $m->dish->image_url, 'why' => $why];

        $after = $items->filter(fn ($m) => ($m->dish->protein_g ?? 0) >= 12)->sortByDesc(fn ($m) => $m->dish->protein_g)->take(3)
            ->map(fn ($m) => $card($m, $m->dish->protein_g.' g protein helps your muscles recover'))->values();
        $before = $items->filter(fn ($m) => ($m->dish->carbs_g ?? 0) >= 30 && ($m->dish->fat_g ?? 99) <= 15 && ($m->dish->kcal ?? 999) <= 550)->sortByDesc(fn ($m) => $m->dish->carbs_g)->take(3)
            ->map(fn ($m) => $card($m, 'Light energy ('.$m->dish->carbs_g.' g carbs) — eat 1–2 hours before you exercise'))->values();
        $hydrate = $items->filter(function ($m) {
            $text = strtolower($m->dish->name.' '.implode(' ', (array) ($m->dish->ingredients ?? [])));
            foreach (self::HYDRATING as $w) {
                if (str_contains($text, $w)) {
                    return $m->dish->sodium !== 'high';
                }
            }
            return false;
        })->take(3)->map(fn ($m) => $card($m, 'Adds water to your day'))->values();
        $salty = $water['behind'] >= 2 || ($water['avg7'] !== null && $water['avg7'] < $water['goal'] * 0.7)
            ? $items->filter(fn ($m) => $m->dish->sodium === 'high')->take(3)->map(fn ($m) => $card($m, 'Saltier dish — have an extra glass of water with it'))->values() : collect();

        return ['date' => Carbon::parse($date)->toDateString(), 'before' => $before->all(), 'after' => $after->all(), 'hydrate' => $hydrate->all(), 'salty' => $salty->all()];
    }

    private function foodInsight(array $foods, array $water, array $workout): ?array
    {
        if ($workout['minutes'] > 0 && $foods['after']) {
            return ['kind' => 'food', 'tone' => 'info', 'icon' => '🍛', 'title' => 'After your workout', 'text' => 'Try '.$foods['after'][0]['name'].' — '.strtolower($foods['after'][0]['why']).'.'];
        }
        if (($water['behind'] >= 2 || ($water['avg7'] !== null && $water['avg7'] < $water['goal'] * 0.7)) && $foods['hydrate']) {
            return ['kind' => 'food', 'tone' => 'info', 'icon' => '🥗', 'title' => 'Eat your water too', 'text' => 'Add '.$foods['hydrate'][0]['name'].' to your lunch — it adds water to your day.'];
        }
        if (! $workout['done_today'] && $foods['before']) {
            return ['kind' => 'food', 'tone' => 'info', 'icon' => '🍛', 'title' => 'Planning to exercise?', 'text' => $foods['before'][0]['name'].' gives light energy if you eat it 1–2 hours before.'];
        }
        return null;
    }

    /** A short friendly message from AI, built only from numbers and dish names. Cached for 3 hours. */
    private function aiMessage(User $user, array $water, array $workout, array $foods): ?string
    {
        if (! $this->ai->enabled()) {
            return null;
        }
        $facts = [
            'water_glasses_today' => $water['glasses'], 'water_goal' => $water['goal'], 'water_expected_by_now' => $water['expected_now'],
            'water_avg_last_7_days' => $water['avg7'], 'water_goal_met_days_last_7' => $water['met_days7'],
            'workout_minutes_today' => $workout['minutes'], 'workout_goal' => $workout['goal'], 'workout_minutes_this_week' => $workout['week_minutes'],
            'workout_streak_days' => $workout['streak'], 'last_workout_type' => $workout['last']['type'] ?? null,
            'menu_after_workout' => array_column($foods['after'], 'name'), 'menu_before_workout' => array_column($foods['before'], 'name'),
            'menu_hydrating' => array_column($foods['hydrate'], 'name'), 'local_time' => now(config('dailytable.timezone'))->format('H:i'),
        ];
        $key = 'coach.'.$user->id.'.'.md5(json_encode($facts));
        return Cache::remember($key, now()->addHours(3), function () use ($facts) {
            $system = "You are Daily Table's friendly wellness coach in Chennai. Using ONLY the facts given, write 2–3 short sentences (max 70 words): "
                .'encourage drinking water if they are behind, motivate a daily workout, and suggest dishes only from the menu lists given. '
                .'Warm, simple English, no lists, no emojis except one at most. Never give medical advice, never mention diseases, medicines or weight loss promises.';
            $text = $this->ai->chat($system, json_encode($facts), 160);
            return $text ? trim($text) : null;
        });
    }
}
