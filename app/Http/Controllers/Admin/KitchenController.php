<?php

namespace App\Http\Controllers\Admin;

use App\Models\Kitchen;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class KitchenController extends CrudController
{
    protected string $model = Kitchen::class;
    protected string $title = 'Kitchens';
    protected string $route = 'admin.kitchens';
    protected array $columns = ['name' => 'Kitchen', 'code' => 'Code', 'gstin' => 'GSTIN', 'is_open' => 'Open'];
    protected bool $canDelete = false;

    protected function fields(?Model $item): array
    {
        return [
            'name' => ['label' => 'Name', 'rules' => 'required|string|max:80'],
            'legal_name' => ['label' => 'Legal name (on invoices)', 'rules' => 'nullable|string|max:120'],
            'code' => ['label' => 'Code (invoice series, e.g. ADY)', 'rules' => 'required|alpha|max:8'],
            'address' => ['label' => 'Address', 'type' => 'textarea', 'rules' => 'required|string|max:300'],
            'lat' => ['label' => 'Latitude', 'type' => 'number', 'rules' => 'required|numeric'],
            'lng' => ['label' => 'Longitude', 'type' => 'number', 'rules' => 'required|numeric'],
            'gstin' => ['label' => 'GSTIN', 'rules' => ['nullable', 'regex:/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/']],
            'fssai' => ['label' => 'FSSAI licence', 'rules' => 'nullable|digits:14'],
            'phone' => ['label' => 'Phone', 'rules' => 'nullable|string|max:15'],
            'service_radius_m' => ['label' => 'Service radius (metres)', 'type' => 'number', 'rules' => 'required|integer|min:500|max:20000'],
            'zone_ids' => ['label' => 'Serves zones', 'type' => 'multiselect', 'options' => Zone::pluck('name', 'id')->all(), 'value' => $item?->zones()->pluck('zones.id')->all() ?? [], 'rules' => 'nullable|array'],
            'is_open' => ['label' => 'Open for orders', 'type' => 'checkbox'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $zones = $data['zone_ids'] ?? [];
        unset($data['zone_ids']);
        $data['code'] = strtoupper($data['code']);
        $item->forceFill($data)->save();
        $item->zones()->sync($zones);
    }
}
