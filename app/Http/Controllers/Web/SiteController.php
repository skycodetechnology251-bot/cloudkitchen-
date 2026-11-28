<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Setting;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    private function seo(): array
    {
        return [
            'title' => Setting::get('seo', 'site_title', 'Daily Table · Healthy home-style lunch in Chennai'),
            'description' => Setting::get('seo', 'meta_description', 'Fresh South Indian lunch from your neighbourhood kitchen, delivered 12–1 PM. Order on the Daily Table app.'),
            'android_url' => Setting::get('app', 'android_url', '#download'),
            'ios_url' => Setting::get('app', 'ios_url'),
        ];
    }

    public function home()
    {
        $zones = Cache::remember('site.zones', 600, fn () => Zone::whereIn('status', ['live', 'testing', 'draft'])->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'testing' THEN 1 ELSE 2 END")->get(['id', 'name', 'slug', 'status']));
        $branches = $this->branchMenus(\App\Models\Kitchen::where('is_open', true)->orderBy('name')->get());
        $marquee = $branches->flatMap(fn ($b) => collect($b['meals'])->flatten(1))->pluck('dish.name')->unique()->values();
        return view('site.home', ['seo' => $this->seo(), 'zones' => $zones, 'branches' => $branches, 'menuNames' => $marquee]);
    }

    public function area(Zone $zone)
    {
        abort_if(in_array($zone->status, ['draft', 'closed'], true), 404);
        $seo = $this->seo();
        $seo['title'] = $zone->seo_title ?: "Healthy lunch delivery in {$zone->name}, Chennai · Daily Table";
        $seo['description'] = $zone->seo_description ?: "Home-style South Indian lunch cooked in {$zone->name}, delivered 12–1 PM. See today's menu and order on the app.";
        $branches = $this->branchMenus($zone->kitchens()->where('is_open', true)->orderBy('name')->get());
        $menu = $branches->flatMap(fn ($b) => collect($b['meals'])->flatten(1))->values(); // for structured data
        return view('site.area', compact('zone', 'seo', 'menu', 'branches'));
    }

    /**
     * Each branch's own menu: its next menu day (today, or the next day it has one), grouped by meal.
     * Menus belong to a branch, so dishes and prices are never mixed between branches.
     */
    private function branchMenus($kitchens)
    {
        $today = now(config('dailytable.timezone'))->toDateString();
        $meals = \App\Support\Meals::enabled();
        return $kitchens->map(function ($k) use ($today, $meals) {
            $date = DailyMenu::where('kitchen_id', $k->id)->where('status', 'open')->whereDate('service_date', '>=', $today)->orderBy('service_date')->value('service_date');
            if (! $date) {
                return null;
            }
            $items = DailyMenu::with('dish')->where('kitchen_id', $k->id)->where('status', 'open')->whereDate('service_date', $date)
                ->whereHas('dish', fn ($q) => $q->where('is_active', true))->get();
            $grouped = [];
            foreach ($meals as $key => $m) {
                $list = $items->where('meal', $key)->values();
                if ($list->count()) {
                    $grouped[$key] = $list;
                }
            }
            return $grouped ? ['kitchen' => $k, 'date' => \Carbon\Carbon::parse($date), 'meals' => $grouped] : null;
        })->filter()->values();
    }

    public function page(string $page)
    {
        return view('site.page', ['page' => $page, 'seo' => $this->seo()]);
    }

    public function notifyMe(Request $r)
    {
        $d = $r->validate(['area' => 'required|string|max:80', 'phone' => ['nullable', 'regex:/^[6-9]\d{9}$/']]);
        Log::channel('single')->info('[notify-me] '.$d['area'].' '.($d['phone'] ?? ''));
        return redirect()->to(route('home').'#areas')->with('status', 'Thanks! We will tell you when we open near '.$d['area'].'.');
    }

    public function sitemap()
    {
        $zones = Zone::where('status', 'live')->get();
        return response()->view('site.sitemap', compact('zones'))->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /staff\nDisallow: /pay\nSitemap: ".url('/sitemap.xml')."\n")->header('Content-Type', 'text/plain');
    }

    /** Razorpay Checkout for the app WebView. Data comes from the app's /checkout response. */
    public function pay(Request $r)
    {
        $d = $r->validate(['key' => 'required|string', 'order' => 'required|string', 'amount' => 'required|integer', 'desc' => 'nullable|string', 'name' => 'nullable|string', 'contact' => 'nullable|string', 'email' => 'nullable|string']);
        return view('site.pay', ['p' => $d]);
    }
}
