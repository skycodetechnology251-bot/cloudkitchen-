<?php

namespace App\Http\Controllers\Admin;

use App\Models\Zone;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ZoneController extends CrudController
{
    protected string $model = Zone::class;
    protected string $title = 'Zones';
    protected string $route = 'admin.zones';
    protected array $columns = ['name' => 'Zone', 'city' => 'City', 'state' => 'State', 'status' => 'Status', 'version' => 'Boundary version'];

    protected function fields(?Model $item): array
    {
        return [
            'name' => ['label' => 'Zone name (area customers know, e.g. Adyar)', 'rules' => 'required|string|max:60'],
            // One block on the form: country / state / city, full address, current location and the Google map.
            'polygon' => ['label' => 'Location & delivery boundary', 'type' => 'zone_location', 'rules' => ['required', 'json', function ($a, $v, $fail) {
                $p = json_decode((string) $v, true);
                if (! is_array($p) || count($p) < 3) {
                    $fail('Draw the delivery boundary: at least 3 points on the map, or use "Circle around the centre".');
                }
            }]],
            'country' => ['type' => 'embedded', 'rules' => 'required|string|max:80'],
            'country_code' => ['type' => 'embedded', 'rules' => 'required|alpha|size:2'],
            'state' => ['type' => 'embedded', 'rules' => 'required|string|max:80'],
            'city' => ['type' => 'embedded', 'rules' => 'required|string|max:80'],
            'address' => ['type' => 'embedded', 'rules' => 'nullable|string|max:255'],
            'pincode' => ['type' => 'embedded', 'rules' => ['nullable', 'regex:/^[A-Za-z0-9 -]{3,12}$/']],
            'center_lat' => ['type' => 'embedded', 'rules' => 'nullable|numeric|between:-90,90'],
            'center_lng' => ['type' => 'embedded', 'rules' => 'nullable|numeric|between:-180,180'],
            'kitchen_ids' => ['label' => 'Branches that deliver here', 'type' => 'multiselect', 'options' => \App\Models\Kitchen::orderBy('name')->pluck('name', 'id')->all(), 'value' => $item?->kitchens()->pluck('kitchens.id')->all() ?? [], 'rules' => 'nullable|array', 'help' => 'Add a new branch in Branches; it can create its zones there too.'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'testing' => 'Testing', 'paused' => 'Paused', 'closed' => 'Closed'], 'rules' => 'required|in:draft,testing,paused,closed', 'help' => 'Going live needs approval: use "Approve go-live" on the list.'],
            'seo_title' => ['label' => 'Area page title (SEO)', 'rules' => 'nullable|string|max:120'],
            'seo_description' => ['label' => 'Area page description (SEO)', 'type' => 'textarea', 'rules' => 'nullable|string|max:300'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $data['polygon'] = is_string($data['polygon']) ? json_decode($data['polygon'], true) : $data['polygon'];
        $data['country_code'] = strtoupper($data['country_code']);
        // No centre given: use the middle of the boundary.
        if (empty($data['center_lat']) && count($data['polygon'])) {
            $data['center_lat'] = round(collect($data['polygon'])->avg(0), 7);
            $data['center_lng'] = round(collect($data['polygon'])->avg(1), 7);
        }
        if ($item->exists && $item->polygon != $data['polygon']) {
            $item->version++;
        }
        $kitchens = array_map('intval', $data['kitchen_ids'] ?? []);
        unset($data['kitchen_ids']);
        if (! $item->slug) {
            $base = Str::slug($data['name']) ?: 'zone';
            $item->slug = $base;
            for ($i = 2; Zone::where('slug', $item->slug)->exists(); $i++) {
                $item->slug = $base.'-'.$i;
            }
        }
        $item->forceFill($data)->save();
        $item->kitchens()->sync($kitchens);
    }

    public function goLive(Zone $zone)
    {
        $checks = [
            'boundary' => count($zone->polygon ?? []) >= 3,
            'kitchen' => $zone->kitchens()->where('is_open', true)->exists(),
            'menu' => \App\Models\DailyMenu::whereIn('kitchen_id', $zone->kitchens()->pluck('kitchens.id'))->whereDate('service_date', '>=', today())->exists(),
            'riders' => \App\Models\User::whereHas('role', fn ($q) => $q->where('slug', 'rider'))->whereHas('kitchens', fn ($q) => $q->whereIn('kitchens.id', $zone->kitchens()->pluck('kitchens.id')))->exists(),
        ];
        if (in_array(false, $checks, true)) {
            return back()->withErrors(['go_live' => 'Not ready: '.implode(', ', array_keys(array_filter($checks, fn ($v) => ! $v))).' missing.']);
        }
        $zone->update(['status' => 'live', 'approved_by' => auth()->id(), 'went_live_at' => now()]);
        AuditService::log('zone.go_live', $zone);
        return back()->with('status', $zone->name.' is live. Its area page and sitemap entry are published.');
    }
}
