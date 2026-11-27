@extends('layouts.admin')
@section('title', 'Orders')
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Orders</h1>
  <form method="get" class="row" style="flex-wrap:nowrap"><label for="oq" class="sr">Search</label><input id="oq" name="q" value="{{ request('q') }}" placeholder="Order number or mobile">
    @if($branches->count() > 1)<label for="ob" class="sr">Branch</label><select id="ob" name="branch" onchange="this.form.submit()"><option value="">All my branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected((int) request('branch') === $b->id)>{{ $b->name }}</option>@endforeach</select>@endif
    <label for="os" class="sr">Status</label><select id="os" name="status" onchange="this.form.submit()"><option value="">All</option>@foreach(['awaiting_payment','confirmed','accepted','preparing','packed','picked_up','delivered','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', $s) }}</option>@endforeach</select></form>
</div>
<div class="card tablebox"><table>
  <thead><tr><th>Order</th><th>Customer</th><th>Branch</th><th>Channel</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th></tr></thead>
  <tbody>@foreach($orders as $o)
    <tr><td><a href="{{ route('admin.orders.show', $o) }}"><b>{{ $o->number }}</b></a></td><td>{{ $o->user?->name ?? 'Walk-in' }}<br><span class="small muted">{{ $o->user?->phone ? substr($o->user->phone,0,2).'••••'.substr($o->user->phone,-3) : '' }}</span></td>
      <td>{{ $o->kitchen->name }}</td><td><span class="chip {{ ['app' => 'blue', 'web' => 'purple', 'pos' => 'amber'][$o->channel] ?? 'grey' }}">{{ strtoupper($o->channel) }}</span></td><td><b>₹{{ $o->rupees() }}</b></td>
      <td><span class="chip {{ ['paid' => 'green', 'pending' => 'amber', 'partially_refunded' => 'sky', 'refunded' => 'purple', 'failed' => 'coral'][$o->payment_status] ?? 'grey' }}">{{ ucfirst(str_replace('_',' ',$o->payment_status)) }}</span></td>
      <td><span class="chip {{ ['awaiting_payment' => 'grey', 'confirmed' => 'blue', 'accepted' => 'blue', 'preparing' => 'coral', 'packed' => 'amber', 'picked_up' => 'purple', 'delivered' => 'green', 'cancelled' => 'grey', 'failed' => 'coral'][$o->status] ?? 'grey' }}">{{ $o->status === 'picked_up' ? 'On the way' : ucfirst(str_replace('_',' ',$o->status)) }}</span></td><td class="small">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M, H:i') }}</td></tr>
  @endforeach</tbody>
</table></div>
<div style="margin-top:14px">{{ $orders->links() }}</div>
@endsection
