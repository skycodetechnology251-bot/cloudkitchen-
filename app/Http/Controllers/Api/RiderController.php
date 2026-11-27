<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeliveryBatch;
use App\Models\DeliveryStop;
use App\Services\DeliveryService;
use Illuminate\Http\Request;

/** Riders see only assigned batches: address, contact route and handling notes. Never health data. */
class RiderController extends Controller
{
    public function duty(Request $r)
    {
        $d = $r->validate(['on_duty' => 'required|boolean']);
        $r->user()->forceFill(['on_duty' => $d['on_duty'], 'last_lat' => $d['on_duty'] ? $r->user()->last_lat : null, 'last_lng' => $d['on_duty'] ? $r->user()->last_lng : null])->save();
        return response()->json(['on_duty' => $r->user()->on_duty]);
    }

    public function location(Request $r)
    {
        abort_unless($r->user()->on_duty, 409, 'Go on duty to share location.');
        $d = $r->validate(['lat' => 'required|numeric', 'lng' => 'required|numeric']);
        $r->user()->forceFill(['last_lat' => $d['lat'], 'last_lng' => $d['lng'], 'last_seen_at' => now()])->save();
        return response()->json(['ok' => true]);
    }

    public function today(Request $r)
    {
        $u = $r->user();
        $batches = DeliveryBatch::with(['kitchen:id,name,address,lat,lng', 'stops.order:id,number,status,address_snapshot,user_id,slot', 'stops.order.items:id,order_id,name,quantity,handling_note,allergens', 'stops.order.user:id,name,phone'])
            ->where('rider_id', $u->id)->whereIn('status', ['offered', 'accepted', 'picked_up'])->latest()->get();
        $done = DeliveryStop::whereHas('batch', fn ($q) => $q->where('rider_id', $u->id))->where('status', 'delivered')->whereDate('delivered_at', today())->count();
        return response()->json([
            'on_duty' => $u->on_duty,
            'delivered_today' => $done,
            'batches' => $batches->map(fn (DeliveryBatch $b) => [
                'id' => $b->id, 'code' => $b->code, 'status' => $b->status,
                'offer_seconds_left' => $b->status === 'offered' && $b->offer_expires_at ? (int) max(0, now()->diffInSeconds($b->offer_expires_at, false)) : null,
                // The rider shows this code at the kitchen counter (the kitchen board shows the same code).
                'pickup_code' => $b->status === 'accepted' ? $b->pickup_code : null,
                'kitchen' => $b->kitchen,
                'stops' => $b->stops->map(fn (DeliveryStop $s) => [
                    'id' => $s->id, 'sequence' => $s->sequence, 'status' => $s->status,
                    'order_id' => $s->order->id, 'order_number' => $s->order->number,
                    'customer' => $s->order->user?->name,
                    'phone' => $s->order->address_snapshot['receiver_phone'] ?? $s->order->user?->phone,
                    'slot' => $s->order->slot,
                    'address' => $s->order->address_snapshot,
                    'items' => $s->order->items->map(fn ($i) => $i->quantity.' × '.$i->name),
                    'handling' => $s->order->items->pluck('handling_note')->filter()->merge($s->order->items->pluck('allergens')->flatten()->filter()->isNotEmpty() ? ['Allergy order · keep sealed and separate'] : [])->unique()->values(),
                ]),
            ]),
        ]);
    }

    public function respond(Request $r, DeliveryBatch $batch, DeliveryService $svc)
    {
        $d = $r->validate(['accept' => 'required|boolean']);
        return response()->json($svc->respond($batch, $r->user(), $d['accept']));
    }

    public function pickup(Request $r, DeliveryBatch $batch, DeliveryService $svc)
    {
        $d = $r->validate(['code' => 'required|string', 'order_ids' => 'required|array']);
        return response()->json($svc->pickup($batch, $r->user(), $d['code'], $d['order_ids']));
    }

    public function deliver(Request $r, DeliveryStop $stop, DeliveryService $svc)
    {
        $d = $r->validate(['otp' => 'required|digits:4']);
        return response()->json($svc->deliver($stop, $r->user(), $d['otp']));
    }

    public function problem(Request $r, DeliveryStop $stop, DeliveryService $svc)
    {
        $d = $r->validate(['reason' => 'required|in:no_answer,wrong_address,damaged,refused,other', 'note' => 'nullable|string|max:200']);
        return response()->json($svc->problem($stop, $r->user(), $d['reason'].($d['note'] ? ': '.$d['note'] : '')));
    }
}
