<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Kitchen;
use App\Services\AuditService;
use Illuminate\Http\Request;

/** Publish the daily menu per kitchen: which dishes, price and portion cap. */
class MenuController extends Controller
{
    public function index(Request $r)
    {
        $kitchens = Kitchen::orderBy('name')->get();
        $kitchen = $kitchens->firstWhere('id', (int) $r->input('kitchen')) ?? $kitchens->first();
        $date = $r->input('date', now('Asia/Kolkata')->addDay()->toDateString());
        $menu = $kitchen ? DailyMenu::with('dish')->where('kitchen_id', $kitchen->id)->whereDate('service_date', $date)->get()->keyBy('dish_id') : collect();
        $dishes = Dish::where('is_active', true)->orderBy('name')->get();
        return view('admin.menus', compact('kitchens', 'kitchen', 'date', 'menu', 'dishes'));
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'kitchen_id' => 'required|exists:kitchens,id', 'date' => 'required|date|after_or_equal:today',
            'rows' => 'array', 'rows.*.on' => 'nullable', 'rows.*.price' => 'nullable|numeric|min:1', 'rows.*.capacity' => 'nullable|integer|min:0', 'cutoff' => 'required|date_format:H:i',
        ]);
        foreach ($d['rows'] ?? [] as $dishId => $row) {
            $existing = DailyMenu::where(['kitchen_id' => $d['kitchen_id'], 'dish_id' => $dishId, 'meal' => 'lunch'])->whereDate('service_date', $d['date'])->first();
            if (empty($row['on'])) {
                if ($existing && $existing->sold === 0 && $existing->reserved === 0) {
                    $existing->delete();
                } elseif ($existing) {
                    $existing->update(['status' => 'paused']);
                }
                continue;
            }
            $values = ['price_paise' => (int) round($row['price'] * 100), 'capacity' => (int) $row['capacity'], 'status' => 'open', 'cutoff_at' => $d['cutoff'].':00'];
            if ($existing) {
                $values['capacity'] = max($values['capacity'], $existing->sold + $existing->reserved);
                $existing->update($values);
            } else {
                DailyMenu::create($values + ['kitchen_id' => $d['kitchen_id'], 'dish_id' => $dishId, 'service_date' => $d['date'], 'meal' => 'lunch']);
            }
        }
        AuditService::log('menu.publish', null, ['kitchen' => $d['kitchen_id'], 'date' => $d['date']]);
        return redirect()->route('admin.menus.index', ['kitchen' => $d['kitchen_id'], 'date' => $d['date']])->with('status', 'Menu published.');
    }

    public function copy(Request $r)
    {
        $d = $r->validate(['kitchen_id' => 'required|exists:kitchens,id', 'from' => 'required|date', 'to' => 'required|date|after_or_equal:today']);
        $n = 0;
        foreach (DailyMenu::where('kitchen_id', $d['kitchen_id'])->whereDate('service_date', $d['from'])->get() as $m) {
            DailyMenu::firstOrCreate(['kitchen_id' => $m->kitchen_id, 'dish_id' => $m->dish_id, 'service_date' => $d['to'], 'meal' => $m->meal], ['price_paise' => $m->price_paise, 'capacity' => $m->capacity, 'cutoff_at' => $m->cutoff_at]);
            $n++;
        }
        return redirect()->route('admin.menus.index', ['kitchen' => $d['kitchen_id'], 'date' => $d['to']])->with('status', "Copied {$n} dishes.");
    }
}
