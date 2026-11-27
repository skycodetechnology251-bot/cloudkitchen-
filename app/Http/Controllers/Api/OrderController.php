<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        return response()->json($r->user()->orders()->with('items:id,order_id,name,quantity', 'invoice:id,order_id,number')->latest()->paginate(20));
    }

    public function show(Request $r, Order $order)
    {
        abort_unless($order->user_id === $r->user()->id, 404);
        $order->load('items', 'events', 'invoice', 'refunds', 'stop.batch.rider:id,name,phone');
        $rider = $order->stop?->batch?->rider;
        $code = in_array($order->status, ['confirmed', 'accepted', 'preparing', 'packed', 'picked_up'], true) ? $order->delivery_code : null;
        $invoiceUrl = $order->invoice ? \Illuminate\Support\Facades\URL::temporarySignedRoute('invoice.public', now()->addMinutes(30), ['order' => $order->id]) : null;
        return response()->json($order->toArray() + ['rider' => $rider ? ['name' => $rider->name, 'phone' => $rider->phone] : null, 'delivery_code' => $code, 'invoice_url' => $invoiceUrl]);
    }

    public function cancel(Request $r, Order $order, OrderService $orders)
    {
        abort_unless($order->user_id === $r->user()->id, 404);
        $cutoff = \Carbon\Carbon::parse($order->service_date->toDateString().' '.config('dailytable.ordering.free_cancel_until'), config('dailytable.timezone'));
        if (! in_array($order->status, ['confirmed', 'awaiting_payment'], true) || now()->greaterThan($cutoff)) {
            throw ValidationException::withMessages(['order' => 'Cooking has started. Please contact support for help with this order.']);
        }
        $reason = $r->validate(['reason' => 'nullable|string|max:120'])['reason'] ?? 'Cancelled by customer';
        $toWallet = $r->boolean('refund_to_wallet', true);
        return response()->json($orders->cancel($order, $reason, $r->user(), $toWallet)->load('refunds'));
    }

    public function invoice(Request $r, Order $order)
    {
        abort_unless($order->user_id === $r->user()->id && $order->invoice, 404);
        return view('invoices.show', ['invoice' => $order->invoice]);
    }
}
