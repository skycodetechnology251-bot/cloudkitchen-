<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kitchen;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private function input(Request $r): array
    {
        return $r->validate([
            'kitchen_id' => 'required|exists:kitchens,id',
            'items' => 'required|array|min:1',
            'items.*.daily_menu_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:20',
            'offer_code' => 'nullable|string|max:30',
            'use_points' => 'boolean',
            'address_id' => 'nullable|integer',
            'slot' => 'nullable|string|max:20',
            'pay' => 'nullable|in:auto,wallet,razorpay',
            'allergy_acknowledged' => 'boolean',
        ]);
    }

    private function items(array $d): array
    {
        $items = [];
        foreach ($d['items'] as $i) {
            $items[(int) $i['daily_menu_id']] = ($items[(int) $i['daily_menu_id']] ?? 0) + (int) $i['quantity'];
        }
        return $items;
    }

    public function quote(Request $r, PricingService $pricing)
    {
        $d = $this->input($r);
        $q = $pricing->quote($r->user(), Kitchen::findOrFail($d['kitchen_id']), $this->items($d), $d + ['order_type' => 'delivery']);
        $wallet = $r->user()->walletOrCreate();
        $q['wallet'] = ['balance_paise' => $wallet->balance_paise, 'would_use_paise' => min($wallet->balance_paise, $q['total_paise'])];
        return response()->json($q);
    }

    public function place(Request $r, OrderService $orders, PricingService $pricing)
    {
        $d = $this->input($r);
        $kitchen = Kitchen::findOrFail($d['kitchen_id']);
        $q = $pricing->quote($r->user(), $kitchen, $this->items($d), $d);
        if (collect($q['warnings'])->contains('type', 'allergy') && empty($d['allergy_acknowledged'])) {
            // Policy: conflicts are shown prominently; repeat one-tap ordering is blocked until acknowledged.
            throw ValidationException::withMessages(['allergy' => 'A dish contains something you are allergic to. Review your basket.']);
        }
        $res = $orders->place($r->user(), $kitchen, $this->items($d), $d + ['channel' => 'app', 'order_type' => 'delivery', 'pay' => $d['pay'] ?? 'auto']);
        return response()->json(['order' => $res['order'], 'payment' => $res['payment']], 201);
    }

    /** Client-side success callback. Only trusted after the signature check; the webhook is the source of truth. */
    public function verify(Request $r, RazorpayService $rzp, OrderService $orders)
    {
        $d = $r->validate(['razorpay_order_id' => 'required', 'razorpay_payment_id' => 'required', 'razorpay_signature' => 'required']);
        if (! $rzp->verifyPayment($d['razorpay_order_id'], $d['razorpay_payment_id'], $d['razorpay_signature'])) {
            throw ValidationException::withMessages(['payment' => 'Payment could not be verified.']);
        }
        $payment = Payment::where('gateway_order_id', $d['razorpay_order_id'])->firstOrFail();
        abort_unless($payment->user_id === $r->user()->id, 404);
        $order = $orders->capture($d['razorpay_order_id'], $d['razorpay_payment_id']);
        return response()->json(['ok' => true, 'order' => $order?->load('invoice')]);
    }

    public function webhook(Request $r, RazorpayService $rzp, OrderService $orders)
    {
        $body = $r->getContent();
        if (! $rzp->verifyWebhook($body, (string) $r->header('X-Razorpay-Signature'))) {
            abort(400, 'Bad signature');
        }
        $eventId = (string) $r->header('X-Razorpay-Event-Id', sha1($body));
        $event = WebhookEvent::firstOrCreate(['provider' => 'razorpay', 'event_id' => $eventId], ['type' => (string) $r->input('event'), 'payload' => $r->all()]);
        if ($event->processed_at) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }
        if (in_array($r->input('event'), ['payment.captured', 'order.paid'], true)) {
            $p = $r->input('payload.payment.entity');
            if ($p && ! empty($p['order_id'])) {
                $orders->capture($p['order_id'], $p['id'], $p);
            }
        }
        if ($r->input('event') === 'payment.failed') {
            $p = $r->input('payload.payment.entity');
            Payment::where('gateway_order_id', $p['order_id'] ?? '')->where('status', 'created')->update(['status' => 'failed']);
        }
        if ($r->input('event') === 'refund.processed') {
            $ref = $r->input('payload.refund.entity');
            \App\Models\Refund::where('gateway_refund_id', $ref['id'] ?? '')->update(['status' => 'succeeded']);
        }
        $event->update(['processed_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
