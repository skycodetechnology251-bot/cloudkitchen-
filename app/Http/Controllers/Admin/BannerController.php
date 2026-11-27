<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use App\Models\Offer;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class BannerController extends CrudController
{
    protected string $model = Banner::class;
    protected string $title = 'Banners & popups';
    protected string $route = 'admin.banners';
    protected array $columns = ['title' => 'Headline', 'placement' => 'Placement', 'starts_at' => 'Starts', 'ends_at' => 'Ends', 'taps' => 'Taps', 'is_active' => 'Active'];

    protected function fields(?Model $item): array
    {
        return [
            'placement' => ['label' => 'Where', 'type' => 'select', 'options' => ['home' => 'Home carousel', 'login' => 'Login banner', 'popup' => 'Offer popup'], 'rules' => 'required|in:home,login,popup'],
            'tag' => ['label' => 'Small label (e.g. THIS WEEK)', 'rules' => 'nullable|string|max:20'],
            'title' => ['label' => 'Headline', 'rules' => 'required|string|max:40'],
            'subtitle' => ['label' => 'Subline', 'rules' => 'nullable|string|max:60'],
            'cta' => ['label' => 'Button text', 'rules' => 'nullable|string|max:20'],
            'link' => ['label' => 'Opens (app screen or URL)', 'rules' => 'nullable|string|max:200', 'help' => 'e.g. menu?category=millet, wallet, plan, https://…'],
            'design' => ['label' => 'Design', 'type' => 'banner_designer', 'rules' => 'nullable|json'],
            'image' => ['label' => 'Image (home 1080×540, login 1080×1200, popup 900×900)', 'type' => 'file', 'column' => 'image_path', 'dir' => 'banners', 'rules' => 'nullable|image|max:500'],
            'offer_id' => ['label' => 'Linked offer', 'type' => 'select', 'options' => ['' => 'None'] + Offer::pluck('code', 'id')->all(), 'rules' => 'nullable|exists:offers,id'],
            'zone_ids' => ['label' => 'Show in zones (empty = all)', 'type' => 'multiselect', 'options' => Zone::pluck('name', 'id')->all(), 'rules' => 'nullable|array'],
            'diet_scope' => ['label' => 'Only for diet', 'type' => 'select', 'options' => ['any' => 'Everyone', 'veg' => 'Vegetarian dishes', 'egg' => 'Egg dishes', 'nonveg' => 'Non-veg dishes'], 'rules' => 'required|in:any,veg,egg,nonveg', 'help' => 'Non-veg promotions are hidden from vegetarian customers automatically.'],
            'frequency' => ['label' => 'Popup frequency', 'type' => 'select', 'options' => ['always' => 'Every time', 'daily' => 'Once a day', 'once' => 'Once per offer'], 'rules' => 'required|in:always,daily,once'],
            'sort' => ['label' => 'Order in carousel', 'type' => 'number', 'rules' => 'nullable|integer'],
            'starts_at' => ['label' => 'Starts', 'type' => 'datetime', 'rules' => 'nullable|date'],
            'ends_at' => ['label' => 'Ends', 'type' => 'datetime', 'rules' => 'nullable|date|after_or_equal:starts_at'],
            'is_active' => ['label' => 'Active', 'type' => 'checkbox'],
        ];
    }
}
