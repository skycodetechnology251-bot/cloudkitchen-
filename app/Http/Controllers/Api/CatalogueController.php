<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Kitchen;
use App\Models\MealResponse;
use App\Services\ServiceabilityService;
use App\Support\Meals;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function serviceability(Request $r, ServiceabilityService $geo)
    {
        $d = $r->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric', 'meal' => 'nullable|string|max:20']);
        $zone = $geo->zoneFor($d['lat'], $d['lng']);
        return response()->json([
            'serviceable' => (bool) $zone,
            'zone' => $zone?->only(['id', 'name', 'status']),
            'kitchens' => $geo->kitchensFor($d['lat'], $d['lng'])->map(fn (Kitchen $k) => $k->only(['id', 'name', 'address', 'distance_m'])),
            'slots' => Meals::get(Meals::valid($d['meal'] ?? 'lunch'))['slots'],
        ]);
    }

    /** Today's menu for a kitchen, filtered by the customer's food rules. */
    public function menu(Request $r)
    {
        // Guests ("Skip" on the app's login) can browse; a signed-in customer also gets their preferences.
        $user = $r->user() ?? auth('sanctum')->user();
        $d = $r->validate(['kitchen_id' => 'required|exists:kitchens,id', 'date' => 'nullable|date', 'category' => 'nullable|string', 'q' => 'nullable|string|max:60', 'meal' => 'nullable|string|max:20']);
        $meal = Meals::valid($d['meal'] ?? 'lunch');
        $date = $d['date'] ?? $this->nextOrderableDate((int) $d['kitchen_id'], $meal);
        $profile = $user?->healthProfile;
        $menus = DailyMenu::with('dish.category')->where('kitchen_id', $d['kitchen_id'])->whereDate('service_date', $date)->where('meal', $meal)
            ->whereHas('dish', function ($q) use ($profile, $d) {
                $q->where('is_active', true);
                if ($profile?->diet === 'veg') {
                    $q->where('diet', 'veg');               // vegetarian profiles see vegetarian food only
                }
                if (! empty($d['q'])) {
                    $q->where('name', 'like', '%'.$d['q'].'%');
                }
            })->get();

        $allergies = array_map('strtolower', $profile?->allergies ?? []);
        $conds = $profile?->conditions_consent_at ? ($profile->conditions ?? []) : [];
        $items = $menus->map(function (DailyMenu $m) use ($allergies, $conds) {
            $dish = $m->dish;
            $conflict = array_values(array_intersect($allergies, array_map('strtolower', $dish->allergens ?? [])));
            $fits = $dish->isReviewed() && $conds && ! array_diff($conds, $dish->suitable_for ?? []);
            return [
                'daily_menu_id' => $m->id,
                'dish' => $dish->only(['id', 'name', 'slug', 'description', 'diet', 'kcal', 'protein_g', 'carbs_g', 'fat_g', 'fibre_g', 'sodium', 'balance', 'ingredients', 'allergens', 'image_url']) + ['category' => $dish->category->name, 'reviewed' => $dish->isReviewed()],
                'price_paise' => $m->price_paise,
                'available' => $m->available(),
                'status' => $m->available() > 0 && $m->status === 'open' ? 'open' : 'sold_out',
                'cutoff' => substr($m->cutoff_at, 0, 5),
                'allergy_conflict' => $conflict,
                'fits_preferences' => $fits && ! $conflict,
            ];
        })->sortByDesc('fits_preferences')->values();

        $response = $user ? MealResponse::where(['user_id' => $user->id, 'meal' => $meal])->whereDate('service_date', $date)->value('response') : null;
        $info = Meals::get($meal);
        return response()->json(['date' => $date, 'meal' => $meal, 'meal_label' => $info['label'], 'slots' => $info['slots'], 'cutoff' => $info['cutoff'], 'items' => $items, 'today_response' => $response]);
    }

    /** The meals a kitchen serves (breakfast, lunch, break time, dinner), each with its next menu date. */
    public function meals(Request $r)
    {
        $d = $r->validate(['kitchen_id' => 'required|exists:kitchens,id']);
        $out = [];
        foreach (Meals::enabled() as $key => $m) {
            $date = $this->nextOrderableDate((int) $d['kitchen_id'], $key);
            $count = DailyMenu::where('kitchen_id', $d['kitchen_id'])->where('meal', $key)->whereDate('service_date', $date)->where('status', 'open')->count();
            $out[] = ['key' => $key, 'label' => $m['label'], 'icon' => $m['icon'], 'color' => $m['color'], 'date' => $date, 'cutoff' => $m['cutoff'], 'slots' => $m['slots'], 'dishes' => $count];
        }
        return response()->json($out);
    }

    /** Today until today's ordering closes; after that, the next day that has a published menu. */
    private function nextOrderableDate(int $kitchenId, string $meal = 'lunch'): string
    {
        $tz = config('dailytable.timezone');
        $now = now($tz);
        $today = DailyMenu::where('kitchen_id', $kitchenId)->where('meal', $meal)->whereDate('service_date', $now->toDateString())->min('cutoff_at');
        if ($today && $now->lt(\Carbon\Carbon::parse($now->toDateString().' '.$today, $tz))) {
            return $now->toDateString();
        }
        $next = DailyMenu::where('kitchen_id', $kitchenId)->where('meal', $meal)->whereDate('service_date', '>', $now->toDateString())->orderBy('service_date')->value('service_date');

        return $next ? \Carbon\Carbon::parse($next)->toDateString() : $now->toDateString();
    }

    public function dish(Dish $dish)
    {
        return response()->json($dish->load('category'));
    }

    public function banners(Request $r)
    {
        $d = $r->validate(['placement' => 'required|in:home,login,popup']);
        $diet = $r->user()?->healthProfile?->diet;
        $banners = Banner::live($d['placement'])->get()->filter(function (Banner $b) use ($diet) {
            if ($b->diet_scope === 'any' || ! $diet) {
                return true;
            }
            return $diet === 'nonveg' || $b->diet_scope === $diet || ($diet === 'egg' && $b->diet_scope === 'veg');
        })->values();
        return response()->json($banners->map->only(['id', 'placement', 'tag', 'title', 'subtitle', 'cta', 'link', 'design', 'image_url', 'frequency', 'offer_id']));
    }

    public function bannerTap(Banner $banner)
    {
        $banner->increment('taps');
        return response()->json(['ok' => true]);
    }

    /** Daily "Lunch today?" answer. Never creates an order or a charge. */
    public function mealResponse(Request $r)
    {
        $d = $r->validate(['response' => 'required|in:yes,no', 'date' => 'nullable|date', 'meal' => 'nullable|string|max:20']);
        $date = $d['date'] ?? now(config('dailytable.timezone'))->toDateString();
        MealResponse::updateOrCreate(['user_id' => $r->user()->id, 'meal' => Meals::valid($d['meal'] ?? 'lunch'), 'service_date' => $date], ['response' => $d['response'], 'source' => 'app']);
        return response()->json(['ok' => true, 'response' => $d['response']]);
    }
}
