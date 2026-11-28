<?php

namespace App\Services;

use App\Models\Address;
use App\Models\DailyMenu;
use App\Models\Kitchen;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Messaging\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Order lifecycle: reserve capacity -> pay (wallet and/or Razorpay) -> confirm
 * -> kitchen states -> delivery. Every state change is an append-only event.
 */
class OrderService
{
    /** Allowed transitions and the actor type that may make them. */
    public const FLOW = [
        'confirmed' => ['accepted' => 'kitchen', 'cancelled' => 'staff'],
        'accepted' => ['preparing' => 'kitchen', 'cancelled' => 'staff'],
        'preparing' => ['packed' => 'kitchen'],
        'packed' => ['picked_up' => 'rider'],
        'picked_up' => ['delivered' => 'rider', 'failed' => 'rider'],
    ];

    public function __construct(
        private PricingService $pricing,
        private WalletService $wallet,
        private RazorpayService $razorpay,
        private InvoiceService $invoices,
        private PointsService $points,
        private NotificationService $notify,
    ) {}

    /**
     * Places an order. Returns ['order' => Order, 'payment' => null|razorpay checkout payload].
     * $pay: 'wallet' (wallet only), 'razorpay' (gateway only), 'auto' (wallet first, rest gateway),
     *       or for POS: 'cash' | 'upi_pos' | 'card_pos'.
     */
    public function place(?User $user, Kitchen $kitchen, array $items, array $opts): array
    {
        $quote = $this->pricing->quote($user, $kitchen, $items, $opts);
        $pay = $opts['pay'] ?? 'auto';
        $address = isset($opts['address_id']) && $user ? Address::where('user_id', $user->id)->findOrFail($opts['address_id']) : null;
        if (($opts['order_type'] ?? 'delivery') === 'delivery' && ($opts['channel'] ?? 'app') !== 'pos' && ! $address) {
            throw ValidationException::withMessages(['address_id' => 'Choose a delivery address.']);
        }

        $order = DB::transaction(function () use ($user, $kitchen, $quote, $opts, $pay, $address) {
            $menus = DailyMenu::whereIn('id', array_column($quote['lines'], 'daily_menu_id'))->lockForUpdate()->get()->keyBy('id');
            foreach ($quote['lines'] as $l) {
                if ($menus[$l['daily_menu_id']]->available() < $l['quantity']) {
                    throw ValidationException::withMessages(['items' => $l['name'].' just sold out.']);
                }
            }
            foreach ($quote['lines'] as $l) {
                $menus[$l['daily_menu_id']]->increment('reserved', $l['quantity']);
            }

            $order = Order::create([
                'number' => $this->number($kitchen),
                'user_id' => $user?->id,
                'kitchen_id' => $kitchen->id,
                'address_id' => $address?->id,
                'address_snapshot' => $address?->snapshot(),
                'channel' => $opts['channel'] ?? 'app',
                'order_type' => $opts['order_type'] ?? 'delivery',
                'cashier_id' => $opts['cashier_id'] ?? null,
                'service_date' => $quote['service_date'],
                'meal' => $quote['meal'] ?? 'lunch',
                'slot' => $opts['slot'] ?? null,
                'status' => 'awaiting_payment',
                'payment_status' => 'pending',
                'offer_id' => $quote['offer']['id'] ?? null,
                'points_used' => $quote['points_used'],
                'reserved_until' => now()->addMinutes(config('dailytable.ordering.reservation_minutes')),
            ] + array_intersect_key($quote, array_flip(['subtotal_paise', 'discount_paise', 'delivery_paise', 'taxable_paise', 'cgst_paise', 'sgst_paise', 'round_off_paise', 'total_paise'])));

            foreach ($quote['lines'] as $l) {
                $order->items()->create(collect($l)->except('allergens')->all() + ['allergens' => $l['allergens']]);
            }
            $this->event($order, null, 'awaiting_payment');

            if ($user && $quote['points_used']) {
                $this->points->redeem($user, $quote['points_used'], $order);
            }

            // Wallet portion is taken now, inside the same transaction.
            $walletPart = 0;
            if ($user && in_array($pay, ['wallet', 'auto'], true)) {
                $balance = $user->walletOrCreate()->balance_paise;
                $walletPart = $pay === 'wallet' ? $order->total_paise : min($balance, $order->total_paise);
                if ($walletPart > 0) {
                    $this->wallet->debit($user, $walletPart, 'order', $order, 'Order '.$order->number);
                    $order->update(['wallet_paise' => $walletPart]);
                    $order->payments()->create(['user_id' => $user->id, 'method' => 'wallet', 'amount_paise' => $walletPart, 'status' => 'captured']);
                }
            }

            if (in_array($pay, ['cash', 'upi_pos', 'card_pos'], true)) {
                $order->payments()->create(['user_id' => $user?->id, 'method' => $pay, 'amount_paise' => $order->total_paise, 'status' => 'captured']);
                $walletPart = $order->total_paise;
            }
            return $order;
        });

        $remaining = $order->total_paise - ($order->channel === 'pos' ? $order->total_paise : $order->wallet_paise);
        if ($remaining <= 0) {
            $this->confirm($order->fresh());
            return ['order' => $order->fresh(['items', 'invoice']), 'payment' => null];
        }

        try {
            $rzp = $this->razorpay->createOrder($remaining, $order->number, ['order_id' => (string) $order->id]);
        } catch (Throwable $e) {
            $this->cancel($order, 'Payment could not start', null, refundToWallet: true);
            throw ValidationException::withMessages(['payment' => 'We could not start the payment. Nothing was charged.']);
        }
        $order->payments()->create(['user_id' => $user?->id, 'method' => 'razorpay', 'amount_paise' => $remaining, 'gateway_order_id' => $rzp['id'], 'status' => 'created']);

        return ['order' => $order->fresh(['items']), 'payment' => [
            'key_id' => $this->razorpay->keyId(),
            'razorpay_order_id' => $rzp['id'],
            'amount_paise' => $remaining,
            'name' => config('dailytable.brand'),
            'description' => 'Order '.$order->number,
            'prefill' => ['name' => $user?->name, 'contact' => $user?->phone, 'email' => $user?->email],
        ]];
    }

    /** Called from the verified client callback or the signed webhook. Idempotent. */
    public function capture(string $gatewayOrderId, string $gatewayPaymentId, array $raw = []): ?Order
    {
        $payment = Payment::where('gateway_order_id', $gatewayOrderId)->first();
        if (! $payment) {
            return null;
        }
        if ($payment->status === 'captured') {
            return $payment->payable instanceof Order ? $payment->payable : null;
        }
        $payment->update(['status' => 'captured', 'gateway_payment_id' => $gatewayPaymentId, 'raw' => $raw ?: null]);

        $payable = $payment->payable;
        if ($payable instanceof \App\Models\Wallet) {
            $this->wallet->credit($payable->user, $payment->amount_paise, 'topup', $payment, 'Top-up');
            $bonus = config('dailytable.wallet');
            if ($payment->amount_paise >= $bonus['bonus_points_from_paise']) {
                $this->points->award($payable->user, $bonus['bonus_points'], 'topup_bonus', $payment);
            }
            return null;
        }

        /** @var Order $order */
        $order = $payable;
        if ($order->status === 'cancelled') {
            // Paid after the reservation lapsed: try to take the portions again, else refund.
            if (! $this->reacquire($order)) {
                $this->refundPayment($order, $payment, $payment->amount_paise, 'Paid after the dish sold out');
                $this->notify->notify($order->user, 'order', 'Order could not be completed', 'Your payment arrived after the dish sold out. A full refund has started.');
                return $order;
            }
        }
        $this->confirm($order);
        return $order->fresh();
    }

    public function confirm(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::lockForUpdate()->find($order->id);
            if ($order->payment_status === 'paid') {
                return;
            }
            foreach ($order->items as $item) {
                if ($item->daily_menu_id) {
                    DailyMenu::where('id', $item->daily_menu_id)->update([
                        'reserved' => DB::raw(self::floorZero('reserved', (int) $item->quantity)),
                        'sold' => DB::raw('sold + '.(int) $item->quantity),
                    ]);
                }
            }
            $otp = null;
            if ($order->order_type === 'delivery') {
                $otp = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $order->delivery_otp_hash = Hash::make($otp);
                $order->delivery_code = $otp;
            }
            $from = $order->status;
            $order->status = $order->channel === 'pos' && $order->order_type !== 'delivery' ? 'accepted' : 'confirmed';
            $order->payment_status = 'paid';
            $order->reserved_until = null;
            $order->save();
            $this->event($order, $from, $order->status);
            if ($order->offer_id) {
                $order->offer()->increment('used');
            }

            DB::afterCommit(function () use ($order, $otp) {
                $order->refresh();
                $this->invoices->issue($order);
                $this->points->earnForOrder($order);
                if ($order->user) {
                    $body = 'Order '.$order->number.' is confirmed for '.($order->slot ?: 'today').'.'.($otp ? ' Your delivery code is '.$otp.'.' : '');
                    $this->notify->notify($order->user, 'order', 'Lunch confirmed', $body, [
                        'data' => ['screen' => 'Order', 'order_id' => $order->id, 'delivery_code' => $otp],
                        'wa_template' => 'order_confirmed', 'wa_params' => [$order->user->name, $order->number, $otp ?? '-'],
                    ]);
                }
            });
        });
    }

    public function transition(Order $order, string $to, User $actor, ?string $reason = null): Order
    {
        $allowed = self::FLOW[$order->status] ?? [];
        if (! isset($allowed[$to])) {
            throw ValidationException::withMessages(['status' => "Cannot move an order from {$order->status} to {$to}."]);
        }
        if ($to === 'cancelled') {
            return $this->cancel($order, $reason ?? 'Cancelled by staff', $actor, refundToWallet: false);
        }
        $from = $order->status;
        $order->update(['status' => $to]);
        $this->event($order, $from, $to, $actor, $reason);
        $messages = ['accepted' => 'The kitchen has your order.', 'preparing' => 'Your lunch is being cooked.', 'packed' => 'Packed and checked. A delivery partner is on the way to the kitchen.', 'picked_up' => 'Your lunch is on the way.', 'delivered' => 'Delivered. Enjoy your meal!'];
        if ($order->user && isset($messages[$to])) {
            $this->notify->notify($order->user, 'order', 'Order '.$order->number, $messages[$to], ['data' => ['screen' => 'Order', 'order_id' => $order->id]]);
        }
        return $order;
    }

    public function cancel(Order $order, string $reason, ?User $actor, bool $refundToWallet = true): Order
    {
        return DB::transaction(function () use ($order, $reason, $actor, $refundToWallet) {
            $order = Order::lockForUpdate()->find($order->id);
            if (in_array($order->status, ['cancelled', 'delivered', 'picked_up'], true)) {
                return $order;
            }
            $wasPaid = $order->payment_status === 'paid';
            foreach ($order->items as $item) {
                if (! $item->daily_menu_id) {
                    continue;
                }
                $col = $wasPaid ? 'sold' : 'reserved';
                DailyMenu::where('id', $item->daily_menu_id)->update([$col => DB::raw(self::floorZero($col, (int) $item->quantity))]);
            }
            $from = $order->status;
            $order->update(['status' => 'cancelled', 'cancel_reason' => $reason, 'reserved_until' => null]);
            $this->event($order, $from, 'cancelled', $actor, $reason);

            foreach ($order->payments()->where('status', 'captured')->get() as $p) {
                $this->refundPayment($order, $p, $p->amount_paise, $reason, $refundToWallet || $p->method === 'wallet');
            }
            return $order;
        });
    }

    public function refundPayment(Order $order, Payment $payment, int $amount, string $reason, bool $toWallet = false): Refund
    {
        $already = (int) $order->refunds()->whereIn('status', ['initiated', 'processing', 'succeeded'])->sum('amount_paise');
        $captured = (int) $order->payments()->where('status', 'captured')->sum('amount_paise');
        if ($already + $amount > $captured) {
            throw new RuntimeException('Refund would exceed the amount paid.');
        }
        $toWallet = $toWallet && $order->user;
        $refund = $order->refunds()->create([
            'payment_id' => $payment->id, 'amount_paise' => $amount, 'reason' => $reason,
            'destination' => $toWallet ? 'wallet' : 'source', 'status' => 'initiated', 'approved_by' => auth()->id(),
        ]);
        try {
            if ($toWallet) {
                $this->wallet->credit($order->user, $amount, 'refund', $order, 'Refund '.$order->number);
                $refund->update(['status' => 'succeeded']);
            } elseif ($payment->method === 'razorpay' && $payment->gateway_payment_id) {
                $r = $this->razorpay->refund($payment->gateway_payment_id, $amount, ['order' => $order->number]);
                $refund->update(['status' => 'processing', 'gateway_refund_id' => $r['id'] ?? null]);
            } else {
                $refund->update(['status' => 'succeeded']); // cash/POS: handed back at counter
            }
        } catch (Throwable $e) {
            Log::error('Refund failed for '.$order->number.': '.$e->getMessage());
            $refund->update(['status' => 'failed']);
        }
        $total = (int) $order->refunds()->whereIn('status', ['processing', 'succeeded'])->sum('amount_paise');
        $order->update(['payment_status' => $total >= $captured ? 'refunded' : 'partially_refunded']);
        return $refund;
    }

    /** Releases reservations for unpaid orders whose hold has expired (scheduled every minute). */
    public function releaseExpired(): int
    {
        $n = 0;
        Order::where('status', 'awaiting_payment')->where('reserved_until', '<', now())->each(function (Order $o) use (&$n) {
            $this->cancel($o, 'Payment not completed in time', null, refundToWallet: true);
            $n++;
        });
        return $n;
    }

    private function reacquire(Order $order): bool
    {
        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $m = DailyMenu::lockForUpdate()->find($item->daily_menu_id);
                if (! $m || $m->available() < $item->quantity) {
                    return false;
                }
            }
            foreach ($order->items as $item) {
                DailyMenu::where('id', $item->daily_menu_id)->increment('reserved', $item->quantity);
            }
            $order->update(['status' => 'awaiting_payment']);
            return true;
        });
    }

    private function number(Kitchen $kitchen): string
    {
        return $kitchen->code.now()->format('ymd').strtoupper(Str::random(4));
    }

    private function event(Order $order, ?string $from, string $to, ?User $actor = null, ?string $reason = null): void
    {
        OrderEvent::create(['order_id' => $order->id, 'from_status' => $from, 'to_status' => $to, 'actor_id' => $actor?->id ?? auth()->id(), 'reason' => $reason]);
    }

    /** "column - n, but never below 0" in SQL that works on every database. */
    private static function floorZero(string $column, int $n): string
    {
        return "CASE WHEN {$column} > {$n} THEN {$column} - {$n} ELSE 0 END";
    }
}
