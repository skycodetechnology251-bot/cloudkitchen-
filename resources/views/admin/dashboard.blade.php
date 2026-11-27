@extends('layouts.admin')
@section('title', 'Overview')
@section('content')
@php
  $u = auth()->user();
  $hour = (int) $now->format('G');
  $rs = fn ($p) => '₹'.number_format($p / 100);
  $avg = $today['orders'] ? intdiv($today['sales'], $today['orders']) : 0;
  $pct = function ($a, $b) { if (! $b) { return $a ? null : 0; } return (int) round(($a - $b) / $b * 100); };
  $dOrders = $pct($today['orders'], $yday['orders']);
  $dSales = $pct($today['sales'], $yday['sales']);
  $delivered = $status['delivered'] ?? 0;
  $inKitchen = ($status['confirmed'] ?? 0) + ($status['accepted'] ?? 0) + ($status['preparing'] ?? 0) + ($status['packed'] ?? 0);
  $onWay = $status['picked_up'] ?? 0;
  $mins = $now->diffInMinutes($cutoff, false);
  $cutText = $mins > 0 ? 'Orders for today close in '.($mins >= 60 ? intdiv((int) $mins, 60).'h ' : '').((int) $mins % 60).'m (at '.$cutoff->format('g:i A').')' : 'Today\'s ordering closed at '.$cutoff->format('g:i A').' · customers are ordering for tomorrow';
  $tones = ['teal' => ['#1FA67A', '#0F5C4D'], 'blue' => ['#5B8CF5', '#1F4FC2'], 'coral' => ['#F48A5E', '#C9472A'], 'amber' => ['#F2B33D', '#C27407'], 'purple' => ['#9F70F5', '#5B30C0'], 'green' => ['#3CC184', '#167D49'], 'pink' => ['#EE6AA0', '#B8285F'], 'sky' => ['#36B8DE', '#0A7499']];
  $weekMax = max(1, $week->max('sales'));
  $barTones = ['blue', 'purple', 'pink', 'coral', 'amber', 'sky', 'green'];
  // status mix donut
  $mix = collect([
    ['New', ($status['confirmed'] ?? 0) + ($status['accepted'] ?? 0), '#5B8CF5'],
    ['Cooking', $status['preparing'] ?? 0, '#F48A5E'],
    ['Packed', $status['packed'] ?? 0, '#F2B33D'],
    ['On the way', $onWay, '#9F70F5'],
    ['Delivered', $delivered, '#3CC184'],
    ['Cancelled', $cancelled, '#C9CFD3'],
  ]);
  $mixTotal = $mix->sum(1);
  $stops = []; $acc = 0;
  foreach ($mix as $s) { if ($s[1] > 0) { $from = $acc / max(1, $mixTotal) * 360; $acc += $s[1]; $stops[] = $s[2].' '.round($from, 2).'deg '.round($acc / $mixTotal * 360, 2).'deg'; } }
  $donut = $stops ? 'conic-gradient('.implode(',', $stops).')' : 'conic-gradient(#E9EEF0 0deg 360deg)';
  $chTotal = max(1, $channels->sum());
  $channelRows = [['App', $channels['app'] ?? 0, 'blue'], ['Website', $channels['web'] ?? 0, 'purple'], ['Counter POS', $channels['pos'] ?? 0, 'amber']];
  $topMax = max(1, (int) $top->max('qty'));
  $branchMax = max(1, $branches->max('sales'));
  $statusChip = ['awaiting_payment' => 'grey', 'confirmed' => 'blue', 'accepted' => 'blue', 'preparing' => 'coral', 'packed' => 'amber', 'picked_up' => 'purple', 'delivered' => 'green', 'cancelled' => 'grey', 'failed' => 'coral'];
  $zoneChip = ['live' => 'green', 'testing' => 'amber', 'draft' => 'grey', 'paused' => 'coral', 'closed' => 'grey'];
  $avatarTones = ['blue', 'purple', 'pink', 'coral', 'amber', 'teal', 'sky', 'green'];
  $svg = [
    'pos' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M7 20h10M12 16v4"/>',
    'flame' => '<path d="M12 3c2 3 5 4 5 8a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-4 1-7Z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/>',
    'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    'wallet' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M16 12h2M3 10h18"/>',
    'pin' => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
    'store' => '<path d="M4 10h16l-1-5H5zM5 10v10h14V10M10 20v-5h4v5"/>',
    'bag' => '<path d="M5 8h14l-1 12H6L5 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/>',
    'rupee' => '<path d="M7 5h10M7 9h10M7 5c6 0 6 8 0 8l7 7"/>',
    'avg' => '<path d="M4 18 10 12l4 4 6-8"/><path d="M15 8h5v5"/>',
    'pot' => '<path d="M4 10h16v4a6 6 0 0 1-6 6h-4a6 6 0 0 1-6-6v-4ZM2 10h20M9 6c0-1 1-1 1-2M14 6c0-1 1-1 1-2"/>',
    'bike' => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="M6 17h6l3-7h3M9 7h4"/>',
    'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a5 5 0 0 1 3.5 6"/>',
  ];
  $actions = array_values(array_filter([
    $u->hasPermission('pos.use') ? [route('admin.pos.open'), 'Open POS', 'Bill at the counter', 'pos', 'teal'] : null,
    $u->hasPermission('kitchen.board') && $kitchens->count() ? [route('admin.kitchen', $kitchens->first()), 'Kitchen board', $kitchens->first()->name, 'flame', 'coral'] : null,
    $u->hasPermission('menu.publish') ? [route('admin.menus.index'), 'Daily menu', 'Publish tomorrow', 'calendar', 'amber'] : null,
    $u->hasPermission('orders.view') ? [route('admin.orders.index'), 'Orders', 'Track every order', 'bag', 'blue'] : null,
    $u->hasPermission('fin.reports') ? [route('admin.reports'), 'Reports', 'Income, expenses, GST', 'chart', 'sky'] : null,
    $u->hasPermission('staff.salary') ? [route('admin.salaries'), 'Payroll', 'Pay this month', 'wallet', 'green'] : null,
    $u->hasPermission('zones.edit') && $u->seesAllKitchens() ? [route('admin.branches.create'), 'Add a branch', 'With its zones', 'store', 'purple'] : null,
    $u->hasPermission('zones.edit') ? [route('admin.zones.create'), 'Add a zone', 'New delivery area', 'pin', 'pink'] : null,
  ]));
  $kpis = [
    ['Orders today', number_format($today['orders']), $dOrders, 'vs '.$yday['orders'].' yesterday', 'blue', 'bag'],
    ['Sales today', $rs($today['sales']), $dSales, 'vs '.$rs($yday['sales']).' yesterday', 'purple', 'rupee'],
    ['Average order', $rs($avg), false, 'per paid order today', 'pink', 'avg'],
    ['In the kitchen', number_format($inKitchen), false, 'new, cooking or packed', 'coral', 'pot'],
    ['Out for delivery', number_format($onWay), false, $delivered.' delivered so far', 'amber', 'bike'],
    $newCustomers !== null ? ['New customers', number_format($newCustomers), false, $riders.' riders on duty', 'green', 'users'] : ['Riders on duty', number_format($riders), false, 'at your branch', 'green', 'bike'],
  ];
@endphp

{{-- Hero --}}
<section class="dash-hero">
  <div class="row between" style="align-items:flex-start;gap:16px">
    <div>
      <div class="row" style="gap:8px;margin-bottom:8px">
        <span class="chip" style="background:rgba(255,255,255,.2);color:#fff">{{ $now->format('l, j F Y') }}</span>
        <span class="chip" style="background:rgba(255,255,255,.2);color:#fff">Showing: {{ $scope }}</span>
      </div>
      <h1>Good {{ $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') }}, {{ strtok($u->name, ' ') }} 👋</h1>
      <p style="margin:6px 0 0;opacity:.9;font-weight:600">⏰ {{ $cutText }}</p>
    </div>
    <div class="row" style="gap:8px">
      @if($u->hasPermission('pos.use'))<a class="btn" href="{{ route('admin.pos.open') }}" style="background:#fff;color:#1F4FC2">Open POS</a>@endif
      @if($u->hasPermission('menu.publish'))<a class="btn" href="{{ route('admin.menus.index') }}" style="background:rgba(255,255,255,.18);color:#fff">Publish menu</a>@endif
    </div>
  </div>
  <div class="hero-stats">
    <div class="hero-stat"><b>{{ $rs($today['sales']) }}</b><span>sales today</span></div>
    <div class="hero-stat"><b>{{ $today['orders'] }}</b><span>paid orders</span></div>
    <div class="hero-stat"><b>{{ $today['orders'] ? round($delivered / max(1, $today['orders']) * 100) : 0 }}%</b><span>delivered</span></div>
    <div class="hero-stat"><b>{{ $rs($week->sum('sales')) }}</b><span>last 7 days</span></div>
  </div>
</section>

{{-- KPI cards --}}
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:18px">
  @foreach($kpis as [$label, $value, $delta, $sub, $tone, $icon])
    <div class="kpi t-{{ $tone }}">
      <span class="kpi-ico"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $svg[$icon] !!}</svg></span>
      <div class="kpi-label">{{ $label }}</div>
      <div class="kpi-value">{{ $value }}</div>
      @if($delta !== false)<span class="delta">{{ $delta === null ? 'New' : ($delta >= 0 ? '▲ '.$delta.'%' : '▼ '.abs($delta).'%') }}</span>@endif
      <div class="kpi-sub">{{ $sub }}</div>
    </div>
  @endforeach
</div>

{{-- Quick actions --}}
@if($actions)
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:18px">
    @foreach($actions as [$href, $title, $sub, $icon, $tone])
      <a href="{{ $href }}" class="soft s-{{ $tone }} tile">
        <span class="ico i-{{ $tone }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $svg[$icon] !!}</svg></span>
        <span>{{ $title }}<br><span class="small muted" style="font-weight:600">{{ $sub }}</span></span>
      </a>
    @endforeach
  </div>
@endif

{{-- Sales chart + status donut --}}
<div class="grid" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr);margin-bottom:18px" id="dash-row1">
  <section class="panel">
    <div class="row between"><h2><span class="dot" style="background:#5B8CF5"></span>Sales · last 7 days</h2><span class="chip blue">{{ $week->sum('orders') }} orders · {{ $rs($week->sum('sales')) }}</span></div>
    <div class="sub">Paid orders by service day. Hover a bar for details.</div>
    <div class="bars" role="img" aria-label="Sales for the last 7 days">
      @foreach($week as $i => $d)
        @php
          [$c1, $c2] = $tones[$barTones[$i % 7]];
          $h = $d['sales'] ? max(6, round($d['sales'] / $weekMax * 100)) : 2;
        @endphp
        <div class="b {{ $d['today'] ? 'today' : '' }}" title="{{ $d['date'] }}: {{ $d['orders'] }} orders · {{ $rs($d['sales']) }}">
          <div class="col" style="height:{{ $h }}%;background:linear-gradient(180deg,{{ $c1 }},{{ $c2 }});{{ $d['today'] ? 'box-shadow:0 10px 22px -10px '.$c2 : '' }}">@if($d['sales'])<span>{{ $d['sales'] >= 100000 ? '₹'.round($d['sales'] / 100000, 1).'k' : $rs($d['sales']) }}</span>@endif</div>
          <div class="lbl">{{ $d['label'] }}</div>
        </div>
      @endforeach
    </div>
  </section>
  <section class="panel">
    <h2><span class="dot" style="background:#F48A5E"></span>Today's orders</h2>
    <div class="sub">Where every order is right now.</div>
    <div class="row" style="gap:18px;flex-wrap:nowrap;align-items:center">
      <div class="donut" style="background:{{ $donut }}" role="img" aria-label="{{ $mixTotal }} orders today by status"><div class="mid"><b>{{ $mixTotal }}</b><span>orders</span></div></div>
      <div class="legend">
        @foreach($mix as [$name, $n, $color])<div><i style="background:{{ $color }}"></i>{{ $name }}<b>{{ $n }}</b></div>@endforeach
      </div>
    </div>
  </section>
</div>

{{-- Live pipeline --}}
<section class="panel" style="margin-bottom:18px">
  <div class="row between"><h2><span class="dot" style="background:#9F70F5"></span>Live order pipeline</h2>@if($u->hasPermission('orders.view'))<a class="btn ghost small" href="{{ route('admin.orders.index') }}">All orders</a>@endif</div>
  <div class="sub">Today's orders moving from the kitchen to the customer's door.</div>
  <div class="pipe">
    @foreach($pipeline as [$name, $n, $tone, $icon])
      <div class="st t-{{ $tone }}">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icon !!}</svg>
        <b>{{ $n }}</b><span>{{ $name }}{{ ! $loop->last ? ' →' : '' }}</span>
      </div>
    @endforeach
  </div>
</section>

{{-- Branches / top dishes / channels --}}
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr));margin-bottom:18px">
  <section class="panel">
    <div class="row between"><h2><span class="dot" style="background:#3CC184"></span>{{ $branches->count() > 1 ? 'Branches today' : 'Your branch today' }}</h2>@if($u->hasPermission('zones.edit'))<a class="btn ghost small" href="{{ route('admin.branches.index') }}">Branches</a>@endif</div>
    <div class="sub">Sales today and whether tomorrow's menu is published.</div>
    <div class="rank">
      @forelse($branches as $i => $b)
        @php [$c1, $c2] = $tones[['green', 'blue', 'coral', 'purple', 'amber', 'pink', 'sky', 'teal'][$i % 8]]; @endphp
        <div class="r">
          <span class="n" style="background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }})">{{ $b['k']->code ? mb_substr($b['k']->code, 0, 2) : $i + 1 }}</span>
          <div>
            <div class="row between small" style="flex-wrap:nowrap"><b>{{ $b['k']->name }} @if(! $b['k']->is_open)<span class="chip grey" style="font-size:10px">Closed</span>@endif</b><b>{{ $rs($b['sales']) }}</b></div>
            <div class="track"><i style="width:{{ round($b['sales'] / $branchMax * 100) }}%;background:linear-gradient(90deg,{{ $c1 }},{{ $c2 }})"></i></div>
            <div class="small muted" style="margin-top:4px">{{ $b['orders'] }} orders · tomorrow: @if($b['tomorrow'])<span style="color:#167D49;font-weight:800">{{ $b['tomorrow'] }} dishes ✓</span>@else<span style="color:#C9472A;font-weight:800">menu not published</span>@endif</div>
          </div>
          <span></span>
        </div>
      @empty
        <p class="muted">No branch assigned yet.</p>
      @endforelse
    </div>
  </section>

  <section class="panel">
    <h2><span class="dot" style="background:#F2B33D"></span>Top dishes · 7 days</h2>
    <div class="sub">Most ordered plates across {{ $branches->count() > 1 ? 'your branches' : 'the branch' }}.</div>
    <div class="rank">
      @forelse($top as $i => $d)
        @php [$c1, $c2] = $tones[['amber', 'coral', 'pink', 'purple', 'blue', 'sky'][$i % 6]]; @endphp
        <div class="r">
          <span class="n" style="background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }})">{{ $i + 1 }}</span>
          <div>
            <div class="row between small" style="flex-wrap:nowrap"><b>{{ $d->name }}</b><span class="muted">{{ $rs($d->amount) }}</span></div>
            <div class="track"><i style="width:{{ round($d->qty / $topMax * 100) }}%;background:linear-gradient(90deg,{{ $c1 }},{{ $c2 }})"></i></div>
          </div>
          <b>{{ $d->qty }}</b>
        </div>
      @empty
        <p class="muted">No dishes sold in the last 7 days yet.</p>
      @endforelse
    </div>
  </section>

  <section class="panel">
    <h2><span class="dot" style="background:#36B8DE"></span>Where orders come from</h2>
    <div class="sub">Paid orders in the last 7 days.</div>
    <div class="rank">
      @foreach($channelRows as [$name, $n, $tone])
        @php [$c1, $c2] = $tones[$tone]; @endphp
        <div class="r">
          <span class="n" style="background:linear-gradient(135deg,{{ $c1 }},{{ $c2 }})">{{ mb_substr($name, 0, 1) }}</span>
          <div>
            <div class="row between small"><b>{{ $name }}</b><span class="muted">{{ round($n / $chTotal * 100) }}%</span></div>
            <div class="track"><i style="width:{{ round($n / $chTotal * 100) }}%;background:linear-gradient(90deg,{{ $c1 }},{{ $c2 }})"></i></div>
          </div>
          <b>{{ $n }}</b>
        </div>
      @endforeach
    </div>
    <div class="mini" style="margin-top:14px">
      <div class="soft s-green" style="padding:10px 12px"><b style="font-size:20px">{{ $riders }}</b><div class="small muted">riders on duty</div></div>
      <div class="soft s-coral" style="padding:10px 12px"><b style="font-size:20px">{{ $cancelled }}</b><div class="small muted">cancelled today</div></div>
    </div>
  </section>
</div>

{{-- Low stock / latest orders / zones --}}
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(320px,100%),1fr))">
  <section class="panel">
    <h2><span class="dot" style="background:#C9472A"></span>Running low today</h2>
    <div class="sub">Dishes with 10 or fewer portions left.</div>
    <div style="display:grid;gap:8px">
      @forelse($low as $m)
        @php $left = max(0, $m->capacity - $m->sold - $m->reserved); @endphp
        <div class="stock {{ $left === 0 ? 'out' : '' }}">
          <span class="ico i-{{ $left === 0 ? 'coral' : 'amber' }}" style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;flex:0 0 34px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">{!! $svg['pot'] !!}</svg></span>
          <div style="flex:1"><b>{{ $m->dish?->name }}</b>@if($kitchens->count() > 1)<div class="small muted">{{ $m->kitchen?->name }}</div>@endif</div>
          <span class="chip {{ $left === 0 ? 'coral' : 'amber' }}">{{ $left === 0 ? 'Sold out' : $left.' left' }}</span>
        </div>
      @empty
        <div class="soft s-green" style="padding:14px"><b>All good</b><div class="small muted">No dish is close to selling out today.</div></div>
      @endforelse
    </div>
  </section>

  <section class="panel">
    <div class="row between"><h2><span class="dot" style="background:#5B8CF5"></span>Latest orders</h2>@if($u->hasPermission('orders.view'))<a class="btn ghost small" href="{{ route('admin.orders.index') }}">See all</a>@endif</div>
    <div class="sub">The newest orders as they come in.</div>
    @forelse($recent as $i => $o)
      @php $name = $o->user?->name ?? 'Walk-in'; @endphp
      <div class="ord">
        <span class="avatar i-{{ $avatarTones[crc32($name) % 8] }}" aria-hidden="true">{{ strtoupper(mb_substr($name, 0, 1)) }}</span>
        <div><a href="{{ route('admin.orders.show', $o) }}"><b>{{ $o->number }}</b></a><div class="small muted">{{ $name }} · {{ strtoupper($o->channel) }}@if($kitchens->count() > 1) · {{ $o->kitchen?->name }}@endif</div></div>
        <b>₹{{ $o->rupees() }}</b>
        <span class="chip {{ $statusChip[$o->status] ?? 'grey' }}">{{ ucfirst(str_replace('_', ' ', $o->status === 'picked_up' ? 'on the way' : $o->status)) }}</span>
      </div>
    @empty
      <div class="soft s-blue" style="padding:14px"><b>No orders yet</b><div class="small muted">New orders will appear here.</div></div>
    @endforelse
  </section>

  <section class="panel">
    <div class="row between"><h2><span class="dot" style="background:#EE6AA0"></span>Delivery zones</h2>@if($u->hasPermission('zones.edit'))<a class="btn ghost small" href="{{ route('admin.zones.create') }}">+ Add zone</a>@endif</div>
    <div class="sub">Areas you deliver to and their status.</div>
    <div style="display:grid;gap:8px">
      @forelse($zones as $z)
        <div class="row between soft s-{{ $zoneChip[$z->status] === 'green' ? 'green' : ($zoneChip[$z->status] === 'amber' ? 'amber' : 'pink') }}" style="padding:10px 12px;flex-wrap:nowrap">
          <div><b>{{ $z->name }}</b><div class="small muted">{{ $z->city ?: '—' }} · {{ $z->kitchens_count }} {{ $z->kitchens_count == 1 ? 'branch' : 'branches' }}</div></div>
          <span class="chip {{ $zoneChip[$z->status] ?? 'grey' }}">{{ ucfirst($z->status) }}</span>
        </div>
      @empty
        <p class="muted">No zones yet.</p>
      @endforelse
    </div>
  </section>
</div>
@endsection
@push('head')
<style>@media (max-width:1000px){#dash-row1{grid-template-columns:1fr!important}}</style>
@endpush
