<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Branches = kitchens. Each branch has its own address and map pin, invoice series, POS, kitchen board,
 * daily menu, team, and the delivery zones it serves. Zones can be picked or created right in the form.
 */
class BranchController extends Controller
{
    public function index()
    {
        $today = now(config('dailytable.timezone'))->toDateString();
        $branches = Kitchen::with(['zones:id,name,status'])->withCount('staff')->orderBy('name')->get();
        $stats = Order::whereDate('service_date', $today)->where('payment_status', '!=', 'pending')
            ->selectRaw('kitchen_id, count(*) as orders, sum(total_paise) as sales')->groupBy('kitchen_id')->get()->keyBy('kitchen_id');
        return view('admin.branches.index', compact('branches', 'stats'));
    }

    public function create()
    {
        return $this->form(new Kitchen(['is_open' => true, 'service_radius_m' => 8000]));
    }

    public function edit(Kitchen $kitchen)
    {
        return $this->form($kitchen);
    }

    public function store(Request $r)
    {
        $branch = new Kitchen;
        $this->save($branch, $r);
        AuditService::log('admin.branches.create', $branch);
        return redirect()->route('admin.branches.index')->with('status', "Branch {$branch->name} created with ".$branch->zones()->count().' zone(s).');
    }

    public function update(Request $r, Kitchen $kitchen)
    {
        $this->save($kitchen, $r);
        AuditService::log('admin.branches.update', $kitchen);
        return redirect()->route('admin.branches.index')->with('status', "Branch {$kitchen->name} saved.");
    }

    public function toggle(Kitchen $kitchen)
    {
        $kitchen->forceFill(['is_open' => ! $kitchen->is_open])->save();
        AuditService::log('admin.branches.toggle', $kitchen);
        return back()->with('status', $kitchen->name.($kitchen->is_open ? ' is open for orders.' : ' is closed for orders.'));
    }

    private function form(Kitchen $branch)
    {
        $mine = $branch->exists ? $branch->zones()->pluck('zones.id')->all() : [];
        $zones = Zone::with('kitchens:id,name')->orderBy('name')->get();
        $staff = User::with('role')->whereNotNull('role_id')->where('is_active', true)->orderBy('name')->get()
            ->groupBy(fn ($u) => $u->role->name ?? 'Staff');
        $team = $branch->exists ? $branch->staff()->pluck('users.id')->all() : [];
        $overlays = $zones->filter(fn ($z) => count($z->polygon ?? []) > 2)
            ->map(fn ($z) => ['name' => $z->name, 'polygon' => $z->polygon, 'mine' => in_array($z->id, $mine)])->values()->all();
        return view('admin.branches.form', compact('branch', 'zones', 'mine', 'staff', 'team', 'overlays'));
    }

    private function save(Kitchen $branch, Request $r): void
    {
        $d = $r->validate([
            'name' => 'required|string|max:80',
            'legal_name' => 'nullable|string|max:120',
            'code' => 'required|alpha|max:8|unique:kitchens,code'.($branch->exists ? ','.$branch->id : ''),
            'phone' => 'nullable|string|max:15',
            'gstin' => ['nullable', 'regex:/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'fssai' => 'nullable|digits:14',
            'address' => 'required|string|max:255',
            'country' => 'required|string|max:80',
            'country_code' => 'required|alpha|size:2',
            'state' => 'required|string|max:80',
            'city' => 'required|string|max:80',
            'pincode' => ['nullable', 'regex:/^[A-Za-z0-9 -]{3,12}$/'],
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'required|numeric|min:0.5|max:50',
            'zone_ids' => 'nullable|array',
            'zone_ids.*' => 'integer|exists:zones,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer|exists:users,id',
            'new_zones' => 'nullable|array|max:10',
            'new_zones.*.name' => 'nullable|string|max:60',
            'new_zones.*.radius' => 'nullable|numeric|min:0.5|max:30',
            'new_zones.*.status' => 'nullable|in:draft,testing',
        ], [
            'lat.required' => 'Place the branch on the map: search the address, use your current location, or click the map.',
            'address.required' => 'Enter the branch address.',
        ]);

        $newZones = collect($d['new_zones'] ?? [])->filter(fn ($z) => trim($z['name'] ?? '') !== '')->values();
        $zoneIds = array_map('intval', $d['zone_ids'] ?? []);
        if (! $zoneIds && $newZones->isEmpty()) {
            throw ValidationException::withMessages(['zones' => 'A branch needs at least one delivery zone: tick an existing zone or create a new one below.']);
        }

        DB::transaction(function () use ($branch, $d, $r, $newZones, &$zoneIds) {
            $branch->forceFill([
                'name' => $d['name'], 'legal_name' => $d['legal_name'] ?? null, 'code' => strtoupper($d['code']),
                'phone' => $d['phone'] ?? null, 'gstin' => $d['gstin'] ?? null, 'fssai' => $d['fssai'] ?? null,
                'address' => $d['address'], 'country' => $d['country'], 'country_code' => strtoupper($d['country_code']),
                'state' => $d['state'], 'city' => $d['city'], 'pincode' => $d['pincode'] ?? null,
                'lat' => $d['lat'], 'lng' => $d['lng'],
                'service_radius_m' => (int) round($d['radius_km'] * 1000),
                'is_open' => $r->boolean('is_open'),
            ])->save();

            foreach ($newZones as $z) {
                $zone = Zone::create([
                    'name' => trim($z['name']),
                    'slug' => $this->uniqueSlug($z['name']),
                    'polygon' => self::circle((float) $d['lat'], (float) $d['lng'], (float) ($z['radius'] ?? 3)),
                    'status' => $z['status'] ?? 'testing',
                    'country' => $d['country'], 'country_code' => strtoupper($d['country_code']),
                    'state' => $d['state'], 'city' => $d['city'],
                    'center_lat' => $d['lat'], 'center_lng' => $d['lng'],
                ]);
                AuditService::log('admin.zones.create', $zone);
                $zoneIds[] = $zone->id;
            }

            $branch->zones()->sync($zoneIds);
            $branch->staff()->sync(array_map('intval', $d['staff_ids'] ?? []));
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'zone';
        $slug = $base;
        for ($i = 2; Zone::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }
        return $slug;
    }

    /** A 32-point circle of $km around the point, as [[lat, lng], ...]. */
    public static function circle(float $lat, float $lng, float $km): array
    {
        $out = [];
        for ($i = 0; $i < 32; $i++) {
            $a = 2 * M_PI * $i / 32;
            $out[] = [round($lat + ($km / 111.32) * cos($a), 6), round($lng + ($km / (111.32 * cos(deg2rad($lat)))) * sin($a), 6)];
        }
        return $out;
    }
}
