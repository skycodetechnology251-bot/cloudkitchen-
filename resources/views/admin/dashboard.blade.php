@extends('layouts.admin')
@section('title', 'Overview')
@section('content')
@php($u = auth()->user())
@php($hour = now('Asia/Kolkata')->hour)
@php($tones = ['Orders today' => ['blue', 'receipt'], 'Delivered' => ['green', 'check'], 'Sales today' => ['purple', 'rupee'], 'Riders on duty' => ['amber', 'bike']])
@php($svg = [
  'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>',
  'check' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
  'rupee' => '<path d="M6 4h12M6 9h12M14 4c3 0 4 2.5 4 5s-2 5-6 5H7l8 7"/>',
  'bike' => '<circle cx="5.5" cy="17" r="3.5"/><circle cx="18.5" cy="17" r="3.5"/><path d="M5.5 17 9 9h5l4.5 8M12 9l2-4h3"/>',
  'pos' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M7 20h10M12 16v4M7 8h4M7 12h2"/>',
  'flame' => '<path d="M12 22c4 0 7-3 7-7 0-5-5-7-5-12-3 2-6 6-6 9-1-1-2-2-2-4-2 2-1 4-1 7 0 4 3 7 7 7Z"/>',
  'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
  'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
  'wallet' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M16 12h2M3 10h18"/>',
  'pin' => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
  'store' => '<path d="M4 10h16l-1-5H5zM5 10v10h14V10M10 20v-5h4v5"/>',
])
@php($statusChip = ['delivered' => 'green', 'picked_up' => 'blue', 'packed' => 'sky', 'preparing' => 'amber', 'accepted' => 'amber', 'confirmed' => 'purple', 'pending_payment' => 'grey', 'cancelled' => 'coral', 'failed' => 'coral'])
@php($zoneChip = ['live' => 'green', 'testing' => 'amber', 'draft' => 'grey', 'paused' => 'coral', 'closed' => 'coral'])

<div class="pagehead">
  <h1>Good {{ $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening') }}, {{ strtok($u->name, ' ') }}</h1>
  <p>{{ now('Asia/Kolkata')->format('l, j F') }} · Ordering for today closes at {{ config('dailytable.ordering.default_cutoff', '10:45') }}. Here is how lunch is going.</p>
  <p style="margin-top:8px"><span class="chip" style="background:rgba(255,255,255,.22);color:#fff">Showing: {{ $scope }}</span></p>
</div>

<div class="grid g4" style="margin-bottom:18px">
  @foreach($kpis as $label => $v)
    @php([$tone, $icon] = $tones[$label] ?? ['teal', 'chart'])
    <div class="kpi t-{{ $tone }}">
      <span class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $svg[$icon] !!}</svg></span>
      <div class="kpi-label">{{ $label }}</div><div class="kpi-value">{{ $v }}</div>
    </div>
  @endforeach
</div>

@php($actions = array_filter([
  $u->hasPermission('pos.use') ? [route('admin.pos.open'), 'Open POS', 'Bill at the counter', 'pos', 'teal'] : null,
  $u->hasPermission('kitchen.board') && $kitchens->count() ? [route('admin.kitchen', $kitchens->first()), 'Kitchen board', $kitchens->first()->name, 'flame', 'coral'] : null,
  $u->hasPermission('menu.publish') ? [route('admin.menus.index'), 'Daily menu', 'Publish tomorrow', 'calendar', 'amber'] : null,
  $u->hasPermission('fin.reports') ? [route('admin.reports'), 'Reports', 'Income, expenses, GST', 'chart', 'sky'] : null,
  $u->hasPermission('staff.salary') ? [route('admin.salaries'), 'Payroll', 'Pay this month', 'wallet', 'green'] : null,
  $u->hasPermission('zones.edit') && $u->seesAllKitchens() ? [route('admin.branches.create'), 'Add a branch', 'With its zones', 'store', 'purple'] : null,
  $u->hasPermission('zones.edit') ? [route('admin.zones.create'), 'Add a zone', 'New delivery area', 'pin', 'pink'] : null,
]))
@if($actions)
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(190px,1fr));margin-bottom:18px">
    @foreach($actions as [$href, $title, $sub, $icon, $tone])
      <a href="{{ $href }}" class="soft s-{{ $tone }} tile">
        <span class="ico i-{{ $tone }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $svg[$icon] !!}</svg></span>
        <span>{{ $title }}<br><span class="small muted" style="font-weight:600">{{ $sub }}</span></span>
      </a>
    @endforeach
  </div>
@endif

<div class="grid g2">
  <section class="card" style="border-top:4px solid var(--c-pink)">
    <div class="row between" style="margin-bottom:8px"><h2 style="font-size:18px">Zones</h2>@if($u->hasPermission('zones.edit'))<a class="btn small" href="{{ route('admin.zones.create') }}">+ Add zone</a>@endif</div>
    <table><tbody>
      @forelse($zones as $z)
        <tr><td><b>{{ $z->name }}</b>@if($z->city)<div class="small muted">{{ $z->city }}{{ $z->state ? ', '.$z->state : '' }}</div>@endif</td><td>{{ $z->kitchens_count }} {{ $z->kitchens_count == 1 ? 'branch' : 'branches' }}</td><td><span class="chip {{ $zoneChip[$z->status] ?? 'grey' }}">{{ ucfirst($z->status) }}</span></td></tr>
      @empty
        <tr><td class="muted">No zones yet.</td></tr>
      @endforelse
    </tbody></table>
  </section>
  <section class="card" style="border-top:4px solid var(--c-blue)">
    <h2 style="font-size:18px;margin-bottom:8px">Latest orders</h2>
    <table><tbody>
      @forelse($recent as $o)
        <tr><td><a href="{{ route('admin.orders.show', $o) }}"><b>{{ $o->number }}</b></a></td><td>{{ $o->user?->name ?? 'Walk-in' }}</td><td>₹{{ $o->rupees() }}</td><td><span class="chip {{ $statusChip[$o->status] ?? 'grey' }}">{{ str_replace('_', ' ', $o->status) }}</span></td></tr>
      @empty
        <tr><td class="muted">No orders yet.</td></tr>
      @endforelse
    </tbody></table>
  </section>
</div>
@endsection
