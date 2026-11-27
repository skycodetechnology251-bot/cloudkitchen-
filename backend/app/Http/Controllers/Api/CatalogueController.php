<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Kitchen;
use App\Models\MealResponse;
use App\Services\ServiceabilityService;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function serviceability(Request $r, ServiceabilityService $geo)
    {
        $d = $r->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric']);
        $zone = $geo->zoneFor($d['lat'], $d['lng']);
        return response()->json([
            'serviceable' => (bool) $zone,
            'zone' => $zone?->only(['id', 'name', 'status']),
            'kitchens' => $geo->kitchensFor($d['lat'], $d['lng'])->map(fn (Kitchen $k) => $k->only(['id', 'name', 'address', 'distance_m'])),
            'slots' => config('dailytable.ordering.slots.lunch'),
        ]);
    }

    /** Today's menu for a kitchen, filtered by the customer's food rules. */
    public function menu(Request $r)
    {
        $d = $r->validate(['kitchen_id' => 'required|exists:kitchens,id', 'date' => 'nullable|date', 'category' => 'nullable|string', 'q' => 'nullable|string|max:60']);
        $date = $d['date'] ?? $this->nextOrderableDate((int) $d['kitchen_id']);
        $profile = $r->user()?->healthProfile;
        $menus = DailyMenu::with('dish.category')->where('kitchen_id', $d['kitchen_id'])->whereDate('service_date', $date)->where('meal', 'lunch')
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

        $response = $r->user() ? MealResponse::where(['user_id' => $r->user()->id, 'meal' => 'lunch'])->whereDate('service_date', $date)->value('response') : null;
        return response()->json(['date' => $date, 'meal' => 'lunch', 'items' => $items, 'today_response' => $response]);
    }

    /** Today until today's ordering closes; after that, the next day that has a published menu. */
    private function nextOrderableDate(int $kitchenId): string
    {
        $tz = config('dailytable.timezone');
        $now = now($tz);
        $today = DailyMenu::where('kitchen_id', $kitchenId)->where('meal', 'lunch')->whereDate('service_date', $now->toDateString())->min('cutoff_at');
        if ($today && $now->lt(\Carbon\Carbon::parse($now->toDateString().' '.$today, $tz))) {
            return $now->toDateString();
        }
        $next = DailyMenu::where('kitchen_id', $kitchenId)->where('meal', 'lunch')->whereDate('service_date', '>', $now->toDateString())->orderBy('service_date')->value('service_date');

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
        $d = $r->validate(['response' => 'required|in:yes,no', 'date' => 'nullable|date']);
        $date = $d['date'] ?? now(config('dailytable.timezone'))->toDateString();
        MealResponse::updateOrCreate(['user_id' => $r->user()->id, 'meal' => 'lunch', 'service_date' => $date], ['response' => $d['response'], 'source' => 'app']);
        return response()->json(['ok' => true, 'response' => $d['response']]);
    }
}
