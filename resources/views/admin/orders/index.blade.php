@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Orders</h1>
  <form method="get" class="row" style="flex-wrap:nowrap"><label for="oq" class="sr">Search</label><input id="oq" name="q" value="{{ request('q') }}" placeholder="Order number or mobile">
    <label for="os" class="sr">Status</label><select id="os" name="status" onchange="this.form.submit()"><option value="">All</option>@foreach(['awaiting_payment','confirmed','accepted','preparing','packed','picked_up','delivered','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', $s) }}</option>@endforeach</select></form>
</div>
<div class="card tablebox"><table>
  <thead><tr><th>Order</th><th>Customer</th><th>Kitchen</th><th>Channel</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
  <tbody>@foreach($orders as $o)
    <tr><td><a href="{{ route('admin.orders.show', $o) }}"><b>{{ $o->number }}</b></a></td><td>{{ $o->user?->name ?? 'Walk-in' }}<br><span class="small muted">{{ $o->user?->phone ? substr($o->user->phone,0,2).'••••'.substr($o->user->phone,-3) : '' }}</span></td>
      <td>{{ $o->kitchen->name }}</td><td>{{ strtoupper($o->channel) }}</td><td>₹{{ $o->rupees() }}</td>
      <td><span class="chip {{ $o->payment_status === 'paid' ? '' : 'warn' }}">{{ str_replace('_',' ',$o->payment_status) }}</span></td>
      <td><span class="chip grey">{{ str_replace('_',' ',$o->status) }}</span></td><td class="small">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M, H:i') }}</td></tr>
  @endforeach</tbody>
</table></div>
<div style="margin-top:14px">{{ $orders->links() }}</div>
@endsection
