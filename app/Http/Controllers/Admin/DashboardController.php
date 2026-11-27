<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Overview: today's numbers vs yesterday, last 7 days, live order pipeline, branches, top dishes,
 * order channels, dishes running low, zones and latest orders. Everything is limited to the
 * branches the person may see (head office sees all).
 */
class DashboardController extends Controller
{
    private const PAID = ['paid', 'partially_refunded'];

    public function index()
    {
        $u = auth()->user();
        $ids = $u->branchIds();
        $tz = config('dailytable.timezone', 'Asia/Kolkata');
        $now = now($tz);
        $today = $now->toDateString();
        $yday = $now->copy()->subDay()->toDateString();
        $from = $now->copy()->subDays(6)->toDateString();
        $orders = fn () => $u->limitToBranches(Order::query());

        // Today vs yesterday
        $day = fn (string $d) => $orders()->whereDate('service_date', $d)->whereIn('payment_status', self::PAID)
            ->selectRaw('count(*) as n, coalesce(sum(total_paise),0) as s')->first();
        $t = $day($today);
        $y = $day($yday);

        // Live pipeline + status mix today
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

        // Last 7 days
        $byDay = $orders()->whereBetween('service_date', [$from, $today])->whereIn('payment_status', self::PAID)
            ->selectRaw('service_date as d, count(*) as n, coalesce(sum(total_paise),0) as s')->groupBy('service_date')->get()
            ->keyBy(fn ($r) => Carbon::parse($r->d)->toDateString());
        $week = collect(range(6, 0))->map(function ($i) use ($now, $byDay) {
            $d = $now->copy()->subDays($i);
            $r = $byDay->get($d->toDateString());
            return ['label' => $i === 0 ? 'Today' : $d->format('D'), 'date' => $d->format('j M'), 'orders' => (int) ($r->n ?? 0), 'sales' => (int) ($r->s ?? 0), 'today' => $i === 0];
        });

        // Channels (7 days)
        $channels = $orders()->whereBetween('service_date', [$from, $today])->whereIn('payment_status', self::PAID)
            ->selectRaw('channel, count(*) as n')->groupBy('channel')->pluck('n', 'channel')->map(fn ($n) => (int) $n);

        // Top dishes (7 days)
        $top = DB::table('order_items')->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.service_date', [$from, $today])->whereIn('orders.payment_status', self::PAID)
            ->when($ids !== null, fn ($q) => $q->whereIn('orders.kitchen_id', $ids))
            ->selectRaw('order_items.name, sum(order_items.quantity) as qty, sum(order_items.line_total_paise) as amount')
            ->groupBy('order_items.name')->orderByDesc('qty')->limit(6)->get();

        // Branches today + tomorrow's menu
        $kitchens = $u->accessibleKitchens();
        $branchToday = $orders()->whereDate('service_date', $today)->whereIn('payment_status', self::PAID)
            ->selectRaw('kitchen_id, count(*) as n, coalesce(sum(total_paise),0) as s')->groupBy('kitchen_id')->get()->keyBy('kitchen_id');
        $tomorrowMenu = DailyMenu::whereDate('service_date', $now->copy()->addDay()->toDateString())->whereIn('kitchen_id', $kitchens->pluck('id'))
            ->selectRaw('kitchen_id, count(*) as n')->groupBy('kitchen_id')->pluck('n', 'kitchen_id');
        $branches = $kitchens->map(fn ($k) => ['k' => $k, 'orders' => (int) ($branchToday[$k->id]->n ?? 0), 'sales' => (int) ($branchToday[$k->id]->s ?? 0), 'tomorrow' => (int) ($tomorrowMenu[$k->id] ?? 0)]);

        // Dishes running low today
        $low = DailyMenu::with('dish:id,name', 'kitchen:id,name')->whereDate('service_date', $today)->where('status', 'open')
            ->whereIn('kitchen_id', $kitchens->pluck('id'))->whereRaw('capacity - sold - reserved <= ?', [10])
            ->orderByRaw('capacity - sold - reserved')->limit(6)->get();

        // Ordering cut-off countdown
        [$h, $m] = array_map('intval', explode(':', (string) config('dailytable.ordering.default_cutoff', '10:45')));
        $cutoff = $now->copy()->setTime($h, $m);

        return view('admin.dashboard', [
            'today' => ['orders' => (int) $t->n, 'sales' => (int) $t->s],
            'yday' => ['orders' => (int) $y->n, 'sales' => (int) $y->s],
            'pipeline' => $pipeline, 'status' => $status, 'cancelled' => $cancelled,
            'week' => $week, 'channels' => $channels, 'top' => $top, 'branches' => $branches, 'low' => $low,
            'riders' => $u->limitStaffToBranches(User::where('on_duty', true))->count(),
            'newCustomers' => $ids === null ? User::whereNull('role_id')->where('created_at', '>=', $now->copy()->startOfDay()->utc())->count() : null,
            'zones' => Zone::withCount('kitchens')->when($ids !== null, fn ($q) => $q->whereHas('kitchens', fn ($k) => $k->whereIn('kitchens.id', $ids)))->orderBy('name')->get(),
            'kitchens' => $kitchens,
            'recent' => $orders()->with('user:id,name', 'kitchen:id,name')->latest()->limit(7)->get(),
            'scope' => $u->branchScopeLabel(),
            'cutoff' => $cutoff, 'now' => $now,
        ]);
    }
}
