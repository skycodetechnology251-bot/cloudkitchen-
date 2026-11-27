<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::with('user:id,name,phone', 'kitchen:id,name')->latest();
        if ($s = $r->input('q')) {
            $q->where(fn ($w) => $w->where('number', 'like', "%{$s}%")->orWhereHas('user', fn ($u) => $u->where('phone', 'like', "%{$s}%")));
        }
        if ($st = $r->input('status')) {
            $q->where('status', $st);
        }
        return view('admin.orders.index', ['orders' => $q->paginate(30)->withQueryString()]);
    }

    public function show(Order $order)
    {
        $order->load('items', 'events.order', 'payments', 'refunds', 'invoice', 'user:id,name,phone,email', 'stop.batch.rider:id,name');
        return view('admin.orders.show', compact('order'));
    }

    public function refund(Request $r, Order $order, OrderService $orders)
    {
        $d = $r->validate(['payment_id' => 'required|exists:payments,id', 'amount' => 'required|numeric|min:1', 'reason' => 'required|string|max:160', 'to_wallet' => 'boolean']);
        $payment = Payment::where('payable_type', $order->getMorphClass())->where('payable_id', $order->id)->findOrFail($d['payment_id']);
        $refund = $orders->refundPayment($order, $payment, (int) round($d['amount'] * 100), $d['reason'], $r->boolean('to_wallet'));
        AuditService::log('order.refund', $order, ['refund_id' => $refund->id, 'amount' => $refund->amount_paise]);
        return back()->with('status', 'Refund '.$refund->status.'.');
    }
}
