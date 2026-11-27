<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyMenu;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Http\Request;

/** Kitchen staff see only kitchens they are assigned to, and no health profiles. */
class KitchenController extends Controller
{
    private function kitchen(Request $r, int $id): Kitchen
    {
        $u = $r->user();
        $k = Kitchen::findOrFail($id);
        abort_unless($u->canUseKitchen($id), 403, 'You are not assigned to this kitchen.');
        return $k;
    }

    public function board(Request $r, int $kitchen)
    {
        $k = $this->kitchen($r, $kitchen);
        $date = $r->input('date', now(config('dailytable.timezone'))->toDateString());
        $orders = Order::with('items')->where('kitchen_id', $k->id)->whereDate('service_date', $date)
            ->whereIn('status', ['confirmed', 'accepted', 'preparing', 'packed', 'picked_up', 'delivered'])->orderBy('slot')->get();
        $byDish = DailyMenu::with('dish:id,name')->where('kitchen_id', $k->id)->whereDate('service_date', $date)->get()
            ->map(fn ($m) => ['daily_menu_id' => $m->id, 'dish' => $m->dish->name, 'capacity' => $m->capacity, 'sold' => $m->sold, 'reserved' => $m->reserved, 'status' => $m->status,
                'packed' => $orders->whereIn('status', ['packed', 'picked_up', 'delivered'])->flatMap->items->where('daily_menu_id', $m->id)->sum('quantity')]);
        $batches = \App\Models\DeliveryBatch::with('rider:id,name')->withCount('stops')->where('kitchen_id', $k->id)
            ->whereIn('status', ['offered', 'accepted'])->whereDate('created_at', $date)->get()
            ->map(fn ($b) => ['code' => $b->code, 'status' => $b->status, 'pickup_code' => $b->pickup_code, 'rider' => $b->rider?->name, 'packages' => $b->stops_count]);
        return response()->json([
            'kitchen' => $k->only(['id', 'name']),
            'batches' => $batches,
            'counts' => $orders->countBy('status'),
            'dishes' => $byDish,
            'orders' => $orders->map(fn (Order $o) => [
                'id' => $o->id, 'number' => $o->number, 'status' => $o->status, 'slot' => $o->slot, 'type' => $o->order_type,
                'items' => $o->items->map->only(['name', 'quantity', 'allergens', 'handling_note']),
                'allergy' => $o->items->pluck('allergens')->flatten()->filter()->isNotEmpty(),
            ]),
        ]);
    }

    public function transition(Request $r, Order $order, OrderService $orders)
    {
        $this->kitchen($r, $order->kitchen_id);
        $d = $r->validate(['to' => 'required|in:accepted,preparing,packed,cancelled', 'reason' => 'nullable|string|max:120', 'expected' => 'nullable|string']);
        if (($d['expected'] ?? $order->status) !== $order->status) {
            abort(409, 'This order changed. Refresh the board.');
        }
        return response()->json($orders->transition($order, $d['to'], $r->user(), $d['reason'] ?? null));
    }

    public function menuStatus(Request $r, DailyMenu $menu)
    {
        $this->kitchen($r, $menu->kitchen_id);
        $d = $r->validate(['status' => 'sometimes|in:open,paused', 'capacity' => 'sometimes|integer|min:0', 'reason' => 'required_with:capacity|nullable|string|max:120']);
        $menu->update(collect($d)->except('reason')->all());
        AuditService::log('menu.update', $menu, $d);
        return response()->json($menu);
    }

    /** Operations: group packed orders into a batch and offer it to an on-duty rider. */
    public function dispatch(Request $r, int $kitchen, DeliveryService $delivery)
    {
        $k = $this->kitchen($r, $kitchen);
        $d = $r->validate(['order_ids' => 'required|array|min:1', 'rider_id' => 'required|exists:users,id']);
        $orders = Order::where('kitchen_id', $k->id)->whereIn('id', $d['order_ids'])->where('status', 'packed')->where('order_type', 'delivery')->whereDoesntHave('stop')->get();
        abort_if($orders->isEmpty(), 422, 'No packed delivery orders to send.');
        $rider = User::whereKey($d['rider_id'])->whereHas('role', fn ($q) => $q->where('slug', 'rider'))->firstOrFail();
        $batch = $delivery->createBatch($k, $orders, $rider);
        app(\App\Services\Messaging\PushService::class)->toUser($rider, 'New batch '.$batch->code, $orders->count().' orders at '.$k->name, ['screen' => 'Home']);
        return response()->json($batch->load('stops')->makeVisible('pickup_code'), 201);
    }
}
