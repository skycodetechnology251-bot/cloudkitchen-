<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Services\AuditService;
use App\Support\Meals;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Menu planner: pick a branch, a day and a meal (breakfast, lunch, break time, dinner), then switch dishes
 * on or off with their price and portions. Copy a meal from yesterday or last week in one tap.
 */
class MenuController extends Controller
{
    public function index(Request $r)
    {
        $tz = config('dailytable.timezone');
        $kitchens = $r->user()->accessibleKitchens();
        $kitchen = $kitchens->firstWhere('id', (int) $r->input('kitchen')) ?? $kitchens->first();
        $date = Carbon::parse($r->input('date', now($tz)->addDay()->toDateString()))->toDateString();
        $meals = Meals::all();
        $meal = Meals::valid($r->input('meal', 'lunch'));
        $menu = $kitchen ? DailyMenu::with('dish')->where('kitchen_id', $kitchen->id)->where('meal', $meal)->whereDate('service_date', $date)->get()->keyBy('dish_id') : collect();

        // Dishes for this meal first, then the rest.
        $dishes = Dish::with('category')->where('is_active', true)->orderBy('name')->get()
            ->sortBy(fn ($d) => in_array($meal, $d->meal_types ?? [], true) ? 0 : (empty($d->meal_types) ? 1 : 2))->values();

        // Week strip: how many dishes are published per day and meal.
        $start = now($tz)->startOfDay();
        $counts = $kitchen ? DailyMenu::where('kitchen_id', $kitchen->id)->where('status', 'open')
            ->whereBetween('service_date', [$start->toDateString(), $start->copy()->addDays(13)->toDateString()])
            ->selectRaw('service_date, meal, count(*) as n')->groupBy('service_date', 'meal')->get()
            ->groupBy(fn ($x) => Carbon::parse($x->service_date)->toDateString())->map(fn ($g) => $g->pluck('n', 'meal')) : collect();
        $week = collect(range(0, 13))->map(fn ($i) => $start->copy()->addDays($i));

        return view('admin.menus', compact('kitchens', 'kitchen', 'date', 'meals', 'meal', 'menu', 'dishes', 'counts', 'week'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'kitchen_id' => 'required|exists:kitchens,id', 'date' => 'required|date|after_or_equal:today', 'meal' => 'required|in:'.implode(',', Meals::keys()),
            'rows' => 'array', 'rows.*.on' => 'nullable', 'rows.*.price' => 'nullable|numeric|min:1', 'rows.*.capacity' => 'nullable|integer|min:0', 'cutoff' => 'required|date_format:H:i',
        ]);
        abort_unless($r->user()->canSeeBranch((int) $d['kitchen_id']), 403, 'You can only publish the menu for your own branch.');
        $on = 0;
        foreach ($d['rows'] ?? [] as $dishId => $row) {
            $existing = DailyMenu::where(['kitchen_id' => $d['kitchen_id'], 'dish_id' => $dishId, 'meal' => $d['meal']])->whereDate('service_date', $d['date'])->first();
            if (empty($row['on'])) {
                if ($existing && $existing->sold === 0 && $existing->reserved === 0) {
                    $existing->delete();
                } elseif ($existing) {
                    $existing->update(['status' => 'paused']);
                }
                continue;
            }
            $on++;
            $values = ['price_paise' => (int) round(($row['price'] ?? 0) * 100), 'capacity' => (int) ($row['capacity'] ?? 0), 'status' => 'open', 'cutoff_at' => $d['cutoff'].':00'];
            if ($values['price_paise'] < 100) {
                $values['price_paise'] = Dish::find($dishId)?->price_paise ?? 100;
            }
            if ($existing) {
                $values['capacity'] = max($values['capacity'], $existing->sold + $existing->reserved);
                $existing->update($values);
            } else {
                DailyMenu::create($values + ['kitchen_id' => $d['kitchen_id'], 'dish_id' => $dishId, 'service_date' => $d['date'], 'meal' => $d['meal']]);
            }
        }
        AuditService::log('menu.publish', null, ['kitchen' => $d['kitchen_id'], 'date' => $d['date'], 'meal' => $d['meal']]);
        return redirect()->route('admin.menus.index', ['kitchen' => $d['kitchen_id'], 'date' => $d['date'], 'meal' => $d['meal']])
            ->with('status', Meals::label($d['meal']).' for '.Carbon::parse($d['date'])->format('D j M').' saved: '.$on.' '.($on === 1 ? 'dish' : 'dishes').' on the menu.');
    }

    /** Copy one meal from another day (same meal by default) into this day. */
    public function copy(Request $r)
    {
        $d = $r->validate([
            'kitchen_id' => 'required|exists:kitchens,id', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:today',
            'meal' => 'required|in:'.implode(',', Meals::keys()), 'from_meal' => 'nullable|in:'.implode(',', Meals::keys()),
        ]);
        abort_unless($r->user()->canSeeBranch((int) $d['kitchen_id']), 403, 'You can only copy the menu for your own branch.');
        $fromMeal = $d['from_meal'] ?? $d['meal'];
        $n = 0;
        foreach (DailyMenu::where('kitchen_id', $d['kitchen_id'])->where('meal', $fromMeal)->whereDate('service_date', $d['from'])->where('status', 'open')->get() as $m) {
            DailyMenu::firstOrCreate(['kitchen_id' => $m->kitchen_id, 'dish_id' => $m->dish_id, 'service_date' => $d['to'], 'meal' => $d['meal']],
                ['price_paise' => $m->price_paise, 'capacity' => $m->capacity, 'cutoff_at' => $d['meal'] === $fromMeal ? $m->cutoff_at : Meals::get($d['meal'])['cutoff'].':00', 'status' => 'open']);
            $n++;
        }
        return redirect()->route('admin.menus.index', ['kitchen' => $d['kitchen_id'], 'date' => $d['to'], 'meal' => $d['meal']])
            ->with('status', $n ? "Copied {$n} dishes. Check prices and portions, then press Save." : 'Nothing to copy: that day had no '.strtolower(Meals::label($fromMeal)).' menu.');
    }

    /** Meal times: which meals you serve, order cut-off and delivery windows. */
    public function mealTimes(Request $r)
    {
        abort_unless($r->user()->seesAllKitchens(), 403, 'Only head office can change meal times.');
        $r->validate(['meals' => 'required|array', 'meals.*.cutoff' => 'required|date_format:H:i', 'meals.*.slots' => 'nullable|string|max:120']);
        foreach (Meals::keys() as $k) {
            $in = $r->input("meals.$k", []);
            Meals::save($k, ['enabled' => ! empty($in['enabled']), 'cutoff' => $in['cutoff'] ?? null, 'slots' => preg_split('/\s*,\s*/', (string) ($in['slots'] ?? ''))]);
        }
        AuditService::log('menu.meal_times');
        return back()->with('status', 'Meal times saved. The app shows them straight away.');
    }
}
