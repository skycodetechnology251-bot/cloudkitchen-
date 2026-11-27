<?php

namespace App\Services;

use App\Models\DeliveryBatch;
use App\Models\DeliveryStop;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeliveryService
{
    public function __construct(private OrderService $orders, private ServiceabilityService $geo) {}

    /** Groups packed delivery orders into a batch, ordered by nearest-next, and offers it to a rider. */
    public function createBatch(Kitchen $kitchen, Collection $orders, ?User $rider): DeliveryBatch
    {
        return DB::transaction(function () use ($kitchen, $orders, $rider) {
            $batch = DeliveryBatch::create([
                'code' => 'B-'.strtoupper(Str::random(4)),
                'kitchen_id' => $kitchen->id,
                'rider_id' => $rider?->id,
                'status' => 'offered',
                'pickup_code' => (string) random_int(100, 999),
                'offer_expires_at' => now()->addSeconds(config('dailytable.delivery.offer_seconds')),
            ]);
            $lat = $kitchen->lat;
            $lng = $kitchen->lng;
            $left = $orders->values();
            $seq = 1;
            while ($left->isNotEmpty()) {
                $next = $left->sortBy(fn (Order $o) => $this->geo->distance($lat, $lng, $o->address_snapshot['lat'] ?? $lat, $o->address_snapshot['lng'] ?? $lng))->first();
                $batch->stops()->create(['order_id' => $next->id, 'sequence' => $seq++]);
                $lat = $next->address_snapshot['lat'] ?? $lat;
                $lng = $next->address_snapshot['lng'] ?? $lng;
                $left = $left->reject(fn ($o) => $o->id === $next->id)->values();
            }
            return $batch;
        });
    }

    public function respond(DeliveryBatch $batch, User $rider, bool $accept): DeliveryBatch
    {
        $this->mustOwn($batch, $rider);
        if ($batch->status !== 'offered') {
            throw ValidationException::withMessages(['batch' => 'This batch is no longer open.']);
        }
        if ($batch->offer_expires_at && $batch->offer_expires_at->isPast()) {
            $batch->update(['status' => 'declined', 'rider_id' => null]);
            throw ValidationException::withMessages(['batch' => 'The offer timed out and went back to operations.']);
        }
        $batch->update($accept ? ['status' => 'accepted'] : ['status' => 'declined', 'rider_id' => null]);
        return $batch;
    }

    /** Rider confirms pickup after checking every package and entering the counter code. */
    public function pickup(DeliveryBatch $batch, User $rider, string $code, array $checkedOrderIds): DeliveryBatch
    {
        $this->mustOwn($batch, $rider);
        if ($batch->status !== 'accepted') {
            throw ValidationException::withMessages(['batch' => 'Accept the batch first.']);
        }
        if (! hash_equals($batch->pickup_code, $code)) {
            throw ValidationException::withMessages(['code' => 'Pickup code does not match the counter.']);
        }
        $expected = $batch->stops->pluck('order_id')->sort()->values()->all();
        $given = collect($checkedOrderIds)->map(fn ($v) => (int) $v)->sort()->values()->all();
        if ($expected !== $given) {
            throw ValidationException::withMessages(['packages' => 'Package count does not match. Check every package.']);
        }
        DB::transaction(function () use ($batch, $rider) {
            foreach ($batch->stops as $stop) {
                $this->orders->transition($stop->order, 'picked_up', $rider);
            }
            $batch->update(['status' => 'picked_up', 'picked_up_at' => now()]);
        });
        return $batch->fresh('stops');
    }

    public function deliver(DeliveryStop $stop, User $rider, string $otp): DeliveryStop
    {
        $this->mustOwn($stop->batch, $rider);
        if ($stop->status === 'delivered') {
            return $stop;
        }
        if ($stop->otp_attempts >= 3) {
            throw ValidationException::withMessages(['otp' => 'Too many wrong codes. Call support from the app.']);
        }
        if (! $stop->order->delivery_otp_hash || ! Hash::check($otp, $stop->order->delivery_otp_hash)) {
            $stop->increment('otp_attempts');
            throw ValidationException::withMessages(['otp' => 'That code does not match. '.(3 - $stop->otp_attempts).' tries left.']);
        }
        DB::transaction(function () use ($stop, $rider) {
            $stop->update(['status' => 'delivered', 'delivered_at' => now()]);
            $this->orders->transition($stop->order, 'delivered', $rider);
            if ($stop->batch->stops()->where('status', '!=', 'delivered')->doesntExist()) {
                $stop->batch->update(['status' => 'completed']);
            }
        });
        return $stop->fresh();
    }

    public function problem(DeliveryStop $stop, User $rider, string $reason): DeliveryStop
    {
        $this->mustOwn($stop->batch, $rider);
        $stop->update(['problem' => $reason]);
        AuditService::log('delivery.problem', $stop->order, ['reason' => $reason, 'lat' => $rider->last_lat, 'lng' => $rider->last_lng]);
        return $stop;
    }

    private function mustOwn(DeliveryBatch $batch, User $rider): void
    {
        if ($batch->rider_id !== $rider->id) {
            abort(403, 'This batch is not assigned to you.');
        }
    }
}
