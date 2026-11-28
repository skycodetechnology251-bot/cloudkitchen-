<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Dish;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Support\Meals;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Overview, in plain words: a to-do list for today, one card per meal, today's numbers, and the detailed
 * charts below. Admin (or anyone with several branches) can pick one branch or all; the choice is remembered.
 * Dietitians and doctors get their own dashboard.
 */
class DashboardController extends Controller
{
    private const PAID = ['paid', 'partially_refunded'];

    public function index(Request $r)
    {
        $u = $r->user();
        if (in_array($u->role?->slug, ['dietitian', 'doctor'], true)) {
            return redirect()->route('admin.dietitian');
        }

        // Branch picker: ?branch=ID or ?branch=all, remembered for this sign-in.
        $all = $u->accessibleKitchens();
        if ($r->has('branch')) {
            session(['dash_branch' => $r->input('branch') === 'all' ? null : (int) $r->input('branch')]);
        }
        $branch = session('dash_branch') ? $all->firstWhere('id', session('dash_branch')) : null;
        $ids = $branch ? [$branch->id] : $u->branchIds();             // null = every branch
        $kitchens = $branch ? collect([$branch]) : $all;
        $scope = fn ($q, string $col = 'kitchen_id') => $ids === null ? $q : $q->whereIn($col, $ids);
        $orders = fn () => $scope(Order::query());

        $tz = config('dailytable.timezone', 'Asia/Kolkata');
        $now = now($tz);
        $today = $now->toDateString();
        $yday = $now->copy()->subDay()->toDateString();
        $tomorrow = $now->copy()->addDay()->toDateString();
        $from = $now->copy()->subDays(6)->toDateString();

        $day = fn (string $d) => $orders()->whereDate('service_date', $d)->whereIn('payment_status', self::PAID)
            ->selectRaw('count(*) as n, coalesce(sum(total_paise),0) as s')->first();
        $t = $day($today);
        $y = $day($yday);

        $status = $orders()->whereDate('service_date', $today)->where('status', '!=', 'awaiting_payment')
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status')->map(fn ($n) => (int) $n);
        $pipeline = [
            ['New', ($status['confirmed'] ?? 0) + ($status['accepted'] ?? 0), 'blue', '<path d="M12 5v14M5 12h14"/>'],
            ['Cooking', $status['preparing'] ?? 0, 'coral', '<path d="M12 3c2 3 5 4 5 8a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-4 1-7Z"/>'],
            ['Packed', $status['packed'] ?? 0, 'amber', '<path d="M3 8l9-5 9 5-9 5-9-5ZM3 8v8l9 5 9-5V8"/>'],
            ['On the way', $status['picked_up'] ?? 0, 'purple', '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M6 17h6l3-7h3M9 7h4"/>'],
            ['Delivered', $status['delivered'] ?? 0, 'green', '<path d="m5 12 4 4 10-10"/>'],
        ];
        $cancelled = ($status['cancelled'] ?? 0) + ($status['failed'] ?? 0);

        // Today's meals: orders, money and progress for each meal.
        $byMeal = $orders()->whereDate('service_date', $today)->whereIn('payment_status', self::PAID)
            ->selectRaw("meal, count(*) as n, coalesce(sum(total_paise),0) as s, sum(case when status = 'delivered' then 1 else 0 end) as done, sum(case when status in ('confirmed','accepted','preparing','packed','picked_up') then 1 else 0 end) as open")
            ->groupBy('meal')->get()->keyBy('meal');
        $menuToday = $scope(DailyMenu::query())->whereDate('service_date', $today)->where('status', 'open')->selectRaw('meal, count(*) as n')->groupBy('meal')->pluck('n', 'meal');
        $menuTomorrow = $scope(DailyMenu::query())->whereDate('service_date', $tomorrow)->where('status', 'open')->selectRaw('kitchen_id, meal, count(*) as n')->groupBy('kitchen_id', 'meal')->get();
        $meals = collect(Meals::enabled())->map(function ($m, $k) use ($byMeal, $menuToday, $now) {
            $row = $byMeal->get($k);
            [$h, $mi] = array_map('intval', explode(':', $m['cutoff']));
            $cut = $now->copy()->setTime($h, $mi);
            return $m + ['orders' => (int) ($row->n ?? 0), 'sales' => (int) ($row->s ?? 0), 'done' => (int) ($row->done ?? 0), 'open' => (int) ($row->open ?? 0),
                'dishes' => (int) ($menuToday[$k] ?? 0), 'closed' => $now->gte($cut), 'cutoff_at' => $cut];
        });

        // To-do list in plain words.
        $todo = [];
        foreach ($kitchens as $k) {
            foreach (Meals::enabled() as $mk => $m) {
                $n = (int) ($menuTomorrow->first(fn ($x) => $x->kitchen_id == $k->id && $x->meal === $mk)->n ?? 0);
                if (! $n && $u->hasPermission('menu.publish')) {
                    $todo[] = ['tone' => 'amber', 'icon' => $m['icon'], 'text' => "Publish tomorrow's {$m['label']} menu".($kitchens->count() > 1 ? " · {$k->name}" : ''),
                        'href' => route('admin.menus.index', ['kitchen' => $k->id, 'date' => $tomorrow, 'meal' => $mk]), 'cta' => 'Plan menu'];
                }
            }
        }
        $waiting = $status['confirmed'] ?? 0;
        if ($waiting && $u->hasPermission('kitchen.board') && $kitchens->first()) {
            $todo[] = ['tone' => 'blue', 'icon' => '🔔', 'text' => "{$waiting} new ".($waiting === 1 ? 'order is' : 'orders are').' waiting to be accepted', 'href' => route('admin.kitchen', $kitchens->first()), 'cta' => 'Open kitchen board'];
        }
        $low = DailyMenu::with('dish:id,name', 'kitchen:id,name')->whereDate('service_date', $today)->where('status', 'open')
            ->whereIn('kitchen_id', $kitchens->pluck('id'))->whereRaw('capacity - sold - reserved <= ?', [10])->orderByRaw('capacity - sold - reserved')->limit(6)->get();
        if ($low->count() && $u->hasPermission('menu.publish')) {
            $todo[] = ['tone' => 'coral', 'icon' => '🍲', 'text' => $low->count().' '.($low->count() === 1 ? 'dish is' : 'dishes are').' almost sold out today', 'href' => route('admin.menus.index', ['date' => $today]), 'cta' => 'Add portions'];
        }
        $toReview = Dish::where('is_active', true)->whereNull('reviewed_at')->count();
        if ($toReview && $u->hasPermission('menu.edit')) {
            $todo[] = ['tone' => 'purple', 'icon' => '🩺', 'text' => "{$toReview} ".($toReview === 1 ? 'dish is' : 'dishes are').' waiting for the dietitian', 'href' => route('admin.dishes.index'), 'cta' => 'See dishes'];
        }
        $riders = $ids === null ? User::where('on_duty', true)->count() : User::where('on_duty', true)->whereHas('kitchens', fn ($k) => $k->whereIn('kitchens.id', $ids))->count();
        if (! $riders && $meals->sum('open') > 0) {
            $todo[] = ['tone' => 'coral', 'icon' => '🛵', 'text' => 'No rider is on duty but orders are waiting for delivery', 'href' => route('admin.staff.index'), 'cta' => 'See staff'];
        }

        // Details (charts).
        $byDay = $orders()->whereBetween('service_date', [$from, $today])->whereIn('payment_status', self::PAID)
            ->selectRaw('service_date as d, count(*) as n, coalesce(sum(total_paise),0) as s')->groupBy('service_date')->get()->keyBy(fn ($r) => Carbon::parse($r->d)->toDateString());
        $week = collect(range(6, 0))->map(function ($i) use ($now, $byDay) {
            $d = $now->copy()->subDays($i);
            $row = $byDay->get($d->toDateString());
            return ['label' => $i === 0 ? 'Today' : $d->format('D'), 'date' => $d->format('j M'), 'orders' => (int) ($row->n ?? 0), 'sales' => (int) ($row->s ?? 0), 'today' => $i === 0];
        });
        $channels = $orders()->whereBetween('service_date', [$from, $today])->whereIn('payment_status', self::PAID)->selectRaw('channel, count(*) as n')->groupBy('channel')->pluck('n', 'channel')->map(fn ($n) => (int) $n);
        $top = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.service_date', [$from, $today])->whereIn('orders.payment_status', self::PAID)
            ->when($ids !== null, fn ($q) => $q->whereIn('orders.kitchen_id', $ids))
            ->selectRaw('order_items.name, sum(order_items.quantity) as qty, sum(order_items.line_total_paise) as amount')->groupBy('order_items.name')->orderByDesc('qty')->limit(6)->get();
        $branchToday = $orders()->whereDate('service_date', $today)->whereIn('payment_status', self::PAID)->selectRaw('kitchen_id, count(*) as n, coalesce(sum(total_paise),0) as s')->groupBy('kitchen_id')->get()->keyBy('kitchen_id');
        $branches = $kitchens->map(fn ($k) => ['k' => $k, 'orders' => (int) ($branchToday[$k->id]->n ?? 0), 'sales' => (int) ($branchToday[$k->id]->s ?? 0),
            'tomorrow' => (int) $menuTomorrow->where('kitchen_id', $k->id)->sum('n')]);

        return view('admin.dashboard', [
            'today' => ['orders' => (int) $t->n, 'sales' => (int) $t->s], 'yday' => ['orders' => (int) $y->n, 'sales' => (int) $y->s],
            'pipeline' => $pipeline, 'status' => $status, 'cancelled' => $cancelled, 'meals' => $meals, 'todo' => $todo,
            'week' => $week, 'channels' => $channels, 'top' => $top, 'branches' => $branches, 'low' => $low, 'riders' => $riders,
            'newCustomers' => $ids === null ? User::whereNull('role_id')->where('created_at', '>=', $now->copy()->startOfDay()->utc())->count() : null,
            'zones' => Zone::withCount('kitchens')->when($ids !== null, fn ($q) => $q->whereHas('kitchens', fn ($k) => $k->whereIn('kitchens.id', $ids)))->orderBy('name')->get(),
            'kitchens' => $kitchens, 'allKitchens' => $all, 'branch' => $branch, 'canPickAll' => $u->seesAllKitchens() || $all->count() > 1,
            'recent' => $orders()->with('user:id,name', 'kitchen:id,name')->latest()->limit(7)->get(),
            'scope' => $branch?->name ?? $u->branchScopeLabel(), 'now' => $now,
        ]);
    }
}
