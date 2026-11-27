@extends('layouts.admin')
@section('title', 'Overview')
@section('content')
<h1 style="font-size:28px;margin-bottom:18px">Good {{ now('Asia/Kolkata')->hour < 12 ? 'morning' : 'afternoon' }}, {{ strtok(auth()->user()->name, ' ') }}</h1>
<div class="grid g4" style="margin-bottom:20px">
  @foreach($kpis as $label => $v)<div class="card lift"><div class="small muted" style="font-weight:700">{{ $label }}</div><div style="font-size:30px;font-weight:800">{{ $v }}</div></div>@endforeach
</div>
<div class="grid g2">
  <section class="card"><div class="row between"><h2 style="font-size:18px">Zones</h2>@if(auth()->user()->hasPermission('zones.edit'))<a class="btn small" href="{{ route('admin.zones.create') }}">+ Add zone</a>@endif</div>
    <table><tbody>@foreach($zones as $z)<tr><td><b>{{ $z->name }}</b></td><td>{{ $z->kitchens_count }} kitchen(s)</td><td><span class="chip {{ $z->status === 'live' ? '' : 'grey' }}">{{ ucfirst($z->status) }}</span></td></tr>@endforeach</tbody></table></section>
  <section class="card"><h2 style="font-size:18px">Latest orders</h2>
    <table><tbody>@foreach($recent as $o)<tr><td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td><td>{{ $o->user?->name ?? 'Walk-in' }}</td><td>₹{{ $o->rupees() }}</td><td><span class="chip grey">{{ str_replace('_', ' ', $o->status) }}</span></td></tr>@endforeach</tbody></table></section>
</div>
@endsection
