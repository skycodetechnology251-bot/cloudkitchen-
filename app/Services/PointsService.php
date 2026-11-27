<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;

class PointsService
{
    public function award(User $user, int $points, string $reason, $ref = null): void
    {
        if ($points <= 0) {
            return;
        }
        PointTransaction::create([
            'user_id' => $user->id, 'reason' => $reason, 'points' => $points,
            'reference_type' => $ref?->getMorphClass(), 'reference_id' => $ref?->getKey(),
            'expires_at' => now()->addMonths(config('dailytable.points.expiry_months')),
        ]);
    }

    public function earnForOrder(Order $order): void
    {
        if (! $order->user) {
            return;
        }
        $foodRupees = intdiv($order->subtotal_paise - $order->discount_paise, 100);
        $this->award($order->user, intdiv(max(0, $foodRupees), config('dailytable.points.earn_per_rupees')), 'order', $order);
        if ($order->user->orders()->where('payment_status', 'paid')->count() === 1) {
            $this->award($order->user, config('dailytable.points.bonus.first_order'), 'first_order', $order);
        }
    }

    /** Max points usable on an order and their paise value. */
    public function redeemable(User $user, int $orderPaise): array
    {
        $c = config('dailytable.points');
        $maxValue = intdiv($orderPaise * $c['max_redeem_percent'], 100);
        $balance = $user->pointsBalance();
        $blocks = min(intdiv($balance, $c['redeem_points']), intdiv($maxValue, $c['redeem_value_paise']));
        return ['points' => $blocks * $c['redeem_points'], 'value_paise' => $blocks * $c['redeem_value_paise']];
    }

    public function redeem(User $user, int $points, Order $order): void
    {
        PointTransaction::create(['user_id' => $user->id, 'reason' => 'redeem', 'points' => -$points, 'reference_type' => $order->getMorphClass(), 'reference_id' => $order->id]);
    }
}
