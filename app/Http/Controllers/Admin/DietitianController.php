<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\HealthProfile;
use App\Services\AuditService;
use Illuminate\Http\Request;

/** Dietitian desk: dishes waiting for review (urgent ones first), a simple review form, and anonymous totals. */
class DietitianController extends Controller
{
    public const CONDITIONS = [
        'Diabetes' => 'Low sugar, more fibre, whole grains or millets, controlled rice portion.',
        'High blood pressure' => 'Low salt: no pickles or papad, little added salt.',
        'High cholesterol' => 'Low saturated fat: little ghee, coconut or fried items.',
        'Thyroid' => 'Balanced plate; avoid large amounts of raw cruciferous vegetables.',
        'PCOS' => 'Lower refined carbs, good protein and fibre.',
        'Heart care' => 'Low salt and low saturated fat, more vegetables.',
        'Weight management' => 'Moderate calories, high protein and fibre, small oil.',
    ];
    public const ALLERGENS = ['milk', 'gluten', 'peanut', 'tree nuts', 'sesame', 'soy', 'egg', 'shellfish', 'fish', 'mustard'];

    public function index(Request $r)
    {
        $tz = config('dailytable.timezone');
        $soon = DailyMenu::whereBetween('service_date', [now($tz)->toDateString(), now($tz)->addDays(3)->toDateString()])->pluck('dish_id')->unique();
        $waiting = Dish::with('category')->where('is_active', true)->whereNull('reviewed_at')->orderBy('name')->get();
        $urgent = $waiting->filter(fn ($d) => $soon->contains($d->id))->values();
        $later = $waiting->reject(fn ($d) => $soon->contains($d->id))->values();
        $recent = Dish::where('reviewed_at', '>=', now()->subDays(14))->orderByDesc('reviewed_at')->limit(8)->get();
        $active = Dish::where('is_active', true)->count();
        $reviewed = Dish::where('is_active', true)->whereNotNull('reviewed_at')->get();
        $coverage = collect(array_keys(self::CONDITIONS))->mapWithKeys(fn ($c) => [$c => $reviewed->filter(fn ($d) => in_array($c, $d->suitable_for ?? [], true))->count()]);

        $health = null;
        if ($r->user()->hasPermission('health.view')) {
            $profiles = HealthProfile::whereNotNull('conditions_consent_at')->get();
            $counts = [];
            foreach ($profiles as $p) {
                foreach ((array) $p->conditions as $c) {
                    $counts[$c] = ($counts[$c] ?? 0) + 1;
                }
            }
            arsort($counts);
            $health = ['consented' => $profiles->count(), 'all' => HealthProfile::count(), 'counts' => $counts,
                'veg' => HealthProfile::where('diet', 'veg')->count()];
        }

        return view('admin.dietitian.index', compact('urgent', 'later', 'recent', 'active', 'reviewed', 'coverage', 'health') + ['conditions' => self::CONDITIONS]);
    }

    public function review(Dish $dish)
    {
        return view('admin.dietitian.review', ['dish' => $dish->load('category'), 'conditions' => self::CONDITIONS, 'allergens' => self::ALLERGENS]);
    }

    public function save(Request $r, Dish $dish)
    {
        $d = $r->validate([
            'kcal' => 'nullable|integer|min:0|max:3000', 'protein_g' => 'nullable|integer|min:0|max:300', 'carbs_g' => 'nullable|integer|min:0|max:500',
            'fat_g' => 'nullable|integer|min:0|max:300', 'fibre_g' => 'nullable|integer|min:0|max:200', 'sodium' => 'required|in:low,medium,high',
            'ingredients_text' => 'nullable|string|max:600', 'allergens' => 'nullable|array', 'allergens.*' => 'in:'.implode(',', self::ALLERGENS),
            'suitable_for' => 'nullable|array', 'suitable_for.*' => 'in:'.implode(',', array_keys(self::CONDITIONS)), 'action' => 'required|in:approve,save',
        ]);
        $approve = $d['action'] === 'approve';
        $dish->forceFill([
            'kcal' => $d['kcal'] ?? null, 'protein_g' => $d['protein_g'] ?? null, 'carbs_g' => $d['carbs_g'] ?? null, 'fat_g' => $d['fat_g'] ?? null, 'fibre_g' => $d['fibre_g'] ?? null,
            'sodium' => $d['sodium'], 'allergens' => array_values($d['allergens'] ?? []), 'suitable_for' => array_values($d['suitable_for'] ?? []),
            'ingredients' => array_values(array_filter(array_map('trim', explode(',', (string) ($d['ingredients_text'] ?? ''))))),
            'reviewed_at' => $approve ? now() : null, 'reviewed_by' => $approve ? $r->user()->id : null,
        ])->save();
        AuditService::log($approve ? 'dish.reviewed' : 'dish.review_saved', $dish);
        $next = Dish::where('is_active', true)->whereNull('reviewed_at')->where('id', '!=', $dish->id)->orderBy('name')->first();
        if ($approve && $next && $r->boolean('next')) {
            return redirect()->route('admin.dietitian.review', $next)->with('status', $dish->name.' approved ✓. Next: '.$next->name.'.');
        }
        return redirect()->route('admin.dietitian')->with('status', $dish->name.($approve ? ' approved ✓' : ' saved — still waiting for approval').'.');
    }
}
