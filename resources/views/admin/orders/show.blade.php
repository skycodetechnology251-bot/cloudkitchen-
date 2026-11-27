@extends('layouts.admin')
@section('title', 'Order '.$order->number)
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Order {{ $order->number }}</h1>
  <div class="row">@if($order->invoice)<a class="btn ghost" href="{{ route('admin.invoice', $order->invoice) }}" target="_blank">Invoice {{ $order->invoice->number }}</a>@endif<a class="btn ghost" href="{{ route('admin.orders.index') }}">Back</a></div></div>
<div class="grid g2">
  <section class="card"><h2 style="font-size:18px">Items</h2>
    <table><tbody>@foreach($order->items as $i)<tr><td>{{ $i->quantity }} × {{ $i->name }}@if($i->allergens)<br><span class="small muted">Contains: {{ implode(', ', $i->allergens) }}</span>@endif</td><td style="text-align:right">₹{{ number_format($i->line_total_paise/100, 2) }}</td></tr>@endforeach
      <tr><td class="muted">Discount</td><td style="text-align:right">−₹{{ number_format($order->discount_paise/100, 2) }}</td></tr>
      <tr><td class="muted">Delivery</td><td style="text-align:right">₹{{ number_format($order->delivery_paise/100, 2) }}</td></tr>
      <tr><td class="muted">CGST + SGST</td><td style="text-align:right">₹{{ number_format(($order->cgst_paise + $order->sgst_paise)/100, 2) }}</td></tr>
      <tr><td><b>Total</b></td><td style="text-align:right"><b>₹{{ $order->rupees() }}</b></td></tr></tbody></table>
    <p class="small muted">Customer: {{ $order->user?->name ?? 'Walk-in' }} · {{ $order->address_snapshot ? collect($order->address_snapshot)->only(['flat','building','street','area','city','pincode'])->filter()->join(', ') : ucfirst(str_replace('_',' ',$order->order_type)) }}</p>
    @if($order->stop?->batch?->rider)<p class="small">Delivery partner: <b>{{ $order->stop->batch->rider->name }}</b> · {{ $order->stop->status }}</p>@endif
  </section>
  <section class="card"><h2 style="font-size:18px">Timeline</h2>
    <table><tbody>@foreach($order->events as $e)<tr><td>{{ str_replace('_',' ',$e->to_status) }}@if($e->reason)<br><span class="small muted">{{ $e->reason }}</span>@endif</td><td class="small muted">{{ $e->created_at->timezone('Asia/Kolkata')->format('d M H:i') }}</td></tr>@endforeach</tbody></table>
    <h2 style="font-size:18px;margin-top:16px">Payments</h2>
    <table><tbody>@foreach($order->payments as $p)<tr><td>{{ strtoupper($p->method) }} · {{ $p->status }}<br><span class="small muted">{{ $p->gateway_payment_id }}</span></td><td style="text-align:right">₹{{ number_format($p->amount_paise/100, 2) }}</td></tr>@endforeach
    @foreach($order->refunds as $rf)<tr><td>Refund to {{ $rf->destination }} · {{ $rf->status }}<br><span class="small muted">{{ $rf->reason }}</span></td><td style="text-align:right">−₹{{ number_format($rf->amount_paise/100, 2) }}</td></tr>@endforeach</tbody></table>
    @if(auth()->user()->hasPermission('orders.refund') && $order->payments->where('status','captured')->isNotEmpty())
      <form method="post" action="{{ route('admin.orders.refund', $order) }}" class="card" style="background:var(--bg);margin-top:12px">@csrf
        <div class="grid g2" style="gap:0 12px">
          <div class="field"><label for="rp">Payment</label><select id="rp" name="payment_id">@foreach($order->payments->where('status','captured') as $p)<option value="{{ $p->id }}">{{ strtoupper($p->method) }} ₹{{ number_format($p->amount_paise/100, 2) }}</option>@endforeach</select></div>
          <div class="field"><label for="ra">Amount ₹</label><input id="ra" type="number" step="0.01" name="amount"></div></div>
        <div class="field"><label for="rr">Reason</label><input id="rr" name="reason" required></div>
        <label class="row" style="font-weight:600"><input type="checkbox" name="to_wallet" value="1" checked> Refund to customer's wallet (instant)</label>
        <button class="btn small" style="margin-top:10px">Issue refund</button>
      </form>
    @endif
  </section>
</div>
@endsection
