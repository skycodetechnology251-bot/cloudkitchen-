<?php

namespace App\Http\Controllers\Admin;

use App\Models\Offer;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class OfferController extends CrudController
{
    protected string $model = Offer::class;
    protected string $title = 'Offers';
    protected string $route = 'admin.offers';
    protected array $columns = ['code' => 'Code', 'title' => 'Offer', 'type' => 'Type', 'used' => 'Used', 'ends_at' => 'Ends', 'is_active' => 'Active'];

    protected function fields(?Model $item): array
    {
        return [
            'code' => ['label' => 'Code', 'rules' => 'required|alpha_num|max:30|unique:offers,code'.($item ? ','.$item->id : '')],
            'title' => ['label' => 'Shown to customers', 'rules' => 'required|string|max:80'],
            'type' => ['label' => 'Type', 'type' => 'select', 'options' => ['percent' => 'Percent off', 'flat' => 'Flat amount (paise)', 'free_delivery' => 'Free delivery', 'bonus_points' => 'Bonus points (×100)'], 'rules' => 'required|in:percent,flat,free_delivery,bonus_points'],
            'value' => ['label' => 'Value', 'type' => 'number', 'rules' => 'required|integer|min:0'],
            'min_order_paise' => ['label' => 'Minimum order (₹)', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            'max_discount_paise' => ['label' => 'Maximum discount (₹)', 'type' => 'money', 'rules' => 'nullable|numeric|min:0'],
            'uses_per_customer' => ['label' => 'Uses per customer', 'type' => 'number', 'rules' => 'required|integer|min:1'],
            'total_uses' => ['label' => 'Total uses (budget)', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            'zone_ids' => ['label' => 'Zones (empty = all)', 'type' => 'multiselect', 'options' => Zone::pluck('name', 'id')->all(), 'rules' => 'nullable|array'],
            'days' => ['label' => 'Days (empty = every day)', 'type' => 'multiselect', 'options' => [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'], 'rules' => 'nullable|array'],
            'new_customers_only' => ['label' => 'First order only', 'type' => 'checkbox'],
            'starts_at' => ['label' => 'Starts', 'type' => 'datetime', 'rules' => 'nullable|date'],
            'ends_at' => ['label' => 'Ends', 'type' => 'datetime', 'rules' => 'nullable|date'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }

    protected function filled(Model $item, array $data, Request $r): void
    {
        $data['code'] = strtoupper($data['code']);
        $data['days'] = $data['days'] ? array_map('intval', $data['days']) : null;
        $data['zone_ids'] = $data['zone_ids'] ? array_map('intval', $data['zone_ids']) : null;
        $item->forceFill($data)->save();
    }
}
