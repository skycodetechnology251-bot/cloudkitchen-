@extends('layouts.admin')
@section('title', 'Branches')
@section('content')
@php
  $tones = ['teal', 'blue', 'coral', 'purple', 'amber', 'pink', 'green', 'sky'];
  $zoneTone = ['live' => 'green', 'testing' => 'amber', 'draft' => 'grey', 'paused' => 'coral', 'closed' => 'grey'];
  $open = $branches->where('is_open', true)->count();
@endphp
<div class="pagehead" style="background:linear-gradient(120deg,#167D49 0%,#0F5C4D 40%,#7A3FF2 100%)">
  <div class="row between" style="align-items:flex-end;gap:16px;position:relative;z-index:1">
    <div>
      <h1>Branches</h1>
      <p>Each branch has its own address, delivery zones, team, daily menu, POS and invoice series. Customers are served by the nearest open branch whose zone covers their address.</p>
    </div>
    @if($all)<a class="btn" href="{{ route('admin.branches.create') }}" style="background:#fff;color:#0F5C4D">+ New branch</a>@else<span class="chip" style="background:rgba(255,255,255,.22);color:#fff">Your branch{{ $branches->count() > 1 ? 'es' : '' }} only</span>@endif
  </div>
</div>

<div class="grid g4" style="margin-bottom:16px">
  <div class="kpi t-teal"><div class="kpi-label">Branches</div><div class="kpi-value">{{ $branches->count() }}</div><div class="kpi-sub">{{ $open }} open now</div></div>
  <div class="kpi t-purple"><div class="kpi-label">Zones served</div><div class="kpi-value">{{ $branches->flatMap->zones->unique('id')->count() }}</div><div class="kpi-sub">across all branches</div></div>
  <div class="kpi t-blue"><div class="kpi-label">Orders today</div><div class="kpi-value">{{ $stats->sum('orders') }}</div><div class="kpi-sub">paid orders, all branches</div></div>
  <div class="kpi t-amber"><div class="kpi-label">Sales today</div><div class="kpi-value">₹{{ number_format($stats->sum('sales') / 100) }}</div><div class="kpi-sub">including GST</div></div>
</div>

@if($branches->isEmpty())
  <div class="card" style="text-align:center;padding:40px">
    <h2 style="font-size:20px">No branches yet</h2>
    @if($all)<p class="muted">Create your first branch, place it on the map and give it a delivery zone.</p>
    <a class="btn" href="{{ route('admin.branches.create') }}">+ New branch</a>@else<p class="muted">You're not assigned to a branch yet. Ask head office to add you to one.</p>@endif
  </div>
@else
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(360px,100%),1fr))">
  @foreach($branches as $i => $b)
    @php $tone = $tones[$i % count($tones)]; $st = $stats[$b->id] ?? null; @endphp
    <article class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column">
      <div class="t-{{ $tone }}" style="padding:16px 18px;color:#fff">
        <div class="row between" style="flex-wrap:nowrap;gap:10px">
          <div class="row" style="gap:8px;flex-wrap:nowrap"><b style="font-size:19px">{{ $b->name }}</b><span class="chip" style="background:rgba(255,255,255,.22);color:#fff">{{ $b->code }}</span></div>
          <span class="chip" style="background:{{ $b->is_open ? 'rgba(255,255,255,.92)' : 'rgba(0,0,0,.25)' }};color:{{ $b->is_open ? '#167D49' : '#fff' }}">{{ $b->is_open ? '● Open' : 'Closed' }}</span>
        </div>
        <div style="opacity:.92;font-size:13px;margin-top:6px">{{ $b->address }}@if($b->city), {{ $b->city }}@endif</div>
      </div>
      <div style="padding:14px 18px;display:flex;flex-direction:column;gap:12px;flex:1">
        <div class="grid" style="grid-template-columns:repeat(4,1fr);gap:8px;text-align:center">
          <div class="soft s-purple" style="padding:8px"><b style="font-size:18px">{{ $b->zones->count() }}</b><div class="small muted">zones</div></div>
          <div class="soft s-blue" style="padding:8px"><b style="font-size:18px">{{ $b->staff_count }}</b><div class="small muted">team</div></div>
          <div class="soft s-teal" style="padding:8px"><b style="font-size:18px">{{ $st->orders ?? 0 }}</b><div class="small muted">orders</div></div>
          <div class="soft s-amber" style="padding:8px"><b style="font-size:18px">₹{{ number_format(($st->sales ?? 0) / 100) }}</b><div class="small muted">today</div></div>
        </div>
        <div>
          <div class="small" style="font-weight:800;margin-bottom:6px">Delivers to · up to {{ rtrim(rtrim(number_format($b->service_radius_m / 1000, 1), '0'), '.') }} km</div>
          <div class="row" style="gap:6px">
            @forelse($b->zones as $z)<span class="chip {{ $zoneTone[$z->status] ?? 'grey' }}">{{ $z->name }} · {{ ucfirst($z->status) }}</span>@empty<span class="chip coral">No zone yet: edit to add one</span>@endforelse
          </div>
        </div>
        <div class="row" style="gap:6px;margin-top:auto">
          <a class="btn small" href="{{ route('admin.branches.edit', $b) }}">Edit branch</a>
          @if(auth()->user()->hasPermission('pos.use'))<a class="btn ghost small" href="{{ route('admin.pos', $b) }}">POS</a>@endif
          @if(auth()->user()->hasPermission('kitchen.board'))<a class="btn ghost small" href="{{ route('admin.kitchen', $b) }}">Kitchen board</a>@endif
          <form method="post" action="{{ route('admin.branches.toggle', $b) }}" style="margin-left:auto">@csrf
            <button class="btn ghost small">{{ $b->is_open ? 'Close for orders' : 'Open for orders' }}</button></form>
        </div>
      </div>
    </article>
  @endforeach
</div>
@endif
@endsection
