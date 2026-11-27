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
    protected array $columns = ['name' => 'Zone', 'status' => 'Status', 'version' => 'Boundary version'];

    protected function fields(?Model $item): array
    {
        return [
            'name' => ['label' => 'Zone name', 'rules' => 'required|string|max:60'],
            'polygon' => ['label' => 'Boundary', 'type' => 'map', 'rules' => 'required|json', 'help' => 'Click on the map to draw the boundary. Stored as [[lat,lng],...].'],
            'status' => ['label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'testing' => 'Testing', 'paused' => 'Paused', 'closed' => 'Closed'], 'rules' => 'required|in:draft,testing,paused,closed', 'help' => 'Going live needs approval: use "Approve go-live" on the list.'],
            'seo_title' => ['label' => 'Area page title (SEO)', 'rules' => 'nullable|string|max:120'],
            'seo_description' => ['label' => 'Area page description (SEO)', 'type' => 'textarea', 'rules' => 'nullable|string|max:300'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $data['polygon'] = is_string($data['polygon']) ? json_decode($data['polygon'], true) : $data['polygon'];
        if ($item->exists && $item->polygon != $data['polygon']) {
            $item->version++;
        }
        $item->slug ??= Str::slug($data['name']);
        $item->forceFill($data)->save();
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
