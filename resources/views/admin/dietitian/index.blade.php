@extends('layouts.admin')
@section('title', 'Dietitian desk')
@push('head')
<style>
  .dd-list{display:grid;gap:10px}
  .dd-item{display:flex;align-items:center;gap:14px;padding:12px;border-radius:18px;background:#fff;border:1px solid var(--line);text-decoration:none;color:var(--ink);transition:transform .15s}
  .dd-item:hover{transform:translateX(3px);border-color:var(--acc1)}
  .dd-ph{width:64px;height:64px;border-radius:50%;background:#F3EFE6 center/cover no-repeat;flex:0 0 64px;display:flex;align-items:center;justify-content:center;overflow:hidden}
  .dd-item b{font-size:16px}
  .dd-cov{display:grid;gap:10px}
  .dd-cov .r{display:grid;grid-template-columns:170px 1fr 40px;gap:10px;align-items:center;font-size:14px}
  .dd-cov .t{height:10px;border-radius:99px;background:#EEF2F1;overflow:hidden}.dd-cov .t i{display:block;height:100%;border-radius:99px;background:linear-gradient(90deg,#8CC63F,#1F8A4C)}
</style>
@endpush
@section('content')
@php
  $total = $urgent->count() + $later->count();
  $pct = $active ? round($reviewed->count() / $active * 100) : 0;
@endphp
<div class="row between"><div><h1>Dietitian desk</h1><p class="muted" style="margin:4px 0 0">Check each dish's recipe and nutrition, mark which health needs it suits, then approve it. Approved dishes show “Fits your preferences” to customers who shared those needs.</p></div></div>

<div class="grid g4" style="margin-bottom:18px">
  <div class="kpi t-amber"><div class="kpi-label">Waiting for you</div><div class="kpi-value">{{ $total }}</div><div class="kpi-sub">{{ $urgent->count() }} on menus in the next 3 days</div></div>
  <div class="kpi t-green"><div class="kpi-label">Approved dishes</div><div class="kpi-value">{{ $reviewed->count() }}</div><div class="kpi-sub">{{ $pct }}% of {{ $active }} active dishes</div></div>
  <div class="kpi t-blue"><div class="kpi-label">Approved in 14 days</div><div class="kpi-value">{{ $recent->count() }}</div><div class="kpi-sub">your recent reviews</div></div>
  @if($health)<div class="kpi t-purple"><div class="kpi-label">Customers sharing health needs</div><div class="kpi-value">{{ $health['consented'] }}</div><div class="kpi-sub">of {{ $health['all'] }} food profiles · totals only</div></div>@endif
</div>

<div class="grid" style="grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:18px" id="dd-cols">
  <div>
    <section class="panel" style="margin-bottom:16px">
      <h2 style="font-size:19px">🔥 Urgent — on menus soon</h2>
      <div class="sub">These dishes are on a menu in the next 3 days. Customers will see them without a review.</div>
      <div class="dd-list">
        @forelse($urgent as $d)
          <a class="dd-item" href="{{ route('admin.dietitian.review', $d) }}" style="border-left:5px solid #F2711C">
            <span class="dd-ph" style="{{ $d->image_url ? 'background-image:url('.e($d->image_url).')' : '' }}">@unless($d->image_url)@include('partials.plate', ['size' => 60])@endunless</span>
            <span style="flex:1"><b>{{ $d->name }}</b><br><span class="small muted">{{ $d->category?->name }} · {{ $d->kcal ? $d->kcal.' kcal' : 'no nutrition yet' }}</span></span>
            <span class="btn small">Review →</span>
          </a>
        @empty
          <div class="soft s-green" style="padding:14px;font-weight:700">✓ Nothing urgent. Every dish on the next 3 days' menus is approved.</div>
        @endforelse
      </div>
    </section>
    <section class="panel" style="margin-bottom:16px">
      <h2 style="font-size:19px">🩺 Waiting for review</h2>
      <div class="sub">New dishes, or dishes whose recipe changed since you last approved them.</div>
      <div class="dd-list">
        @forelse($later as $d)
          <a class="dd-item" href="{{ route('admin.dietitian.review', $d) }}">
            <span class="dd-ph" style="{{ $d->image_url ? 'background-image:url('.e($d->image_url).')' : '' }}">@unless($d->image_url)@include('partials.plate', ['size' => 60])@endunless</span>
            <span style="flex:1"><b>{{ $d->name }}</b><br><span class="small muted">{{ $d->category?->name }} · {{ $d->kcal ? $d->kcal.' kcal' : 'no nutrition yet' }}</span></span>
            <span class="btn ghost small">Review →</span>
          </a>
        @empty
          <p class="muted">Nothing else waiting. 🎉</p>
        @endforelse
      </div>
    </section>
    <section class="panel">
      <h2 style="font-size:19px">✓ Recently approved</h2>
      <div class="dd-list" style="margin-top:8px">
        @forelse($recent as $d)
          <a class="dd-item" href="{{ route('admin.dietitian.review', $d) }}"><span style="flex:1"><b>{{ $d->name }}</b><br><span class="small muted">{{ $d->reviewed_at->diffForHumans() }} · {{ $d->suitable_for ? implode(', ', $d->suitable_for) : 'general menu' }}</span></span><span class="chip green">Approved</span></a>
        @empty
          <p class="muted">No reviews yet.</p>
        @endforelse
      </div>
    </section>
  </div>
  <div>
    <section class="panel" style="margin-bottom:16px">
      <h2 style="font-size:19px">🥗 Dishes for each health need</h2>
      <div class="sub">How many approved dishes suit each need. Aim for at least 3 per need so everyone has a choice.</div>
      <div class="dd-cov">
        @php $max = max(1, $coverage->max()); @endphp
        @foreach($coverage as $c => $n)
          <div class="r"><b>{{ $c }}</b><span class="t"><i style="width:{{ round($n / $max * 100) }}%"></i></span><b style="color:{{ $n >= 3 ? '#1F8A4C' : '#C9472A' }}">{{ $n }}</b></div>
        @endforeach
      </div>
    </section>
    @if($health)
      <section class="panel">
        <h2 style="font-size:19px">👥 What customers asked for</h2>
        <div class="sub">Totals only. Names and contact details are never shown here.</div>
        @forelse($health['counts'] as $c => $n)
          <div class="row between" style="padding:8px 0;border-bottom:1px dashed var(--line)"><span>{{ $c }}</span><b>{{ $n }} {{ $n === 1 ? 'customer' : 'customers' }}</b></div>
        @empty
          <p class="muted">No customer has shared health needs yet.</p>
        @endforelse
        <p class="small muted" style="margin-top:8px">{{ $health['veg'] }} customers eat vegetarian only.</p>
      </section>
    @endif
  </div>
</div>
@endsection
@push('head')<style>@media (max-width:1000px){#dd-cols{grid-template-columns:1fr!important}}</style>@endpush
