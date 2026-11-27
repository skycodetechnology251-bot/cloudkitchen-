<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>@yield('title', 'Staff') · Daily Table</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dt.css') }}">
@stack('head')
</head>
<body>
@php($u = auth()->user())
@php($myKitchens = $u->accessibleKitchens())
@php($posKitchen = request()->route('kitchen') instanceof \App\Models\Kitchen && request()->routeIs('admin.pos') ? request()->route('kitchen') : null)
<div class="staff">
  <nav class="side" aria-label="Staff">
    <div class="brand">@include('partials.logo') Daily Table</div>
    <span class="chip" style="align-self:flex-start;margin:0 8px 12px">{{ strtoupper($u->role->name) }}</span>
    @php($links = [
      ['admin.dashboard', 'Overview', null],
      ['admin.orders.index', 'Orders', 'orders.view'],
      ['admin.menus.index', 'Daily menu', 'menu.publish'],
      ['admin.dishes.index', 'Dishes', 'menu.edit'],
      ['admin.zones.index', 'Zones', 'zones.edit'],
      ['admin.kitchens.index', 'Kitchens', 'zones.edit'],
      ['admin.banners.index', 'Banners & popups', 'mkt.banners'],
      ['admin.offers.index', 'Offers', 'mkt.banners'],
      ['admin.expenses.index', 'Purchases & expenses', 'expenses.edit'],
      ['admin.reports', 'Reports', 'fin.reports'],
      ['admin.staff.index', 'Staff', 'staff.manage'],
      ['admin.roles.index', 'Roles & permissions', 'staff.roles'],
      ['admin.settings.index', 'Integrations', 'settings.manage'],
      ['admin.system', 'System & updates', 'settings.manage'],
    ])
    @foreach($links as [$name, $label, $perm])
      @if(! $perm || $u->hasPermission($perm))
        <a href="{{ route($name) }}" class="{{ request()->routeIs(str_replace('.index', '', $name).'*') ? 'on' : '' }}">{{ $label }}</a>
      @endif
    @endforeach
    @if($u->hasPermission('kitchen.board'))
      @foreach($myKitchens as $k)
        <a href="{{ route('admin.kitchen', $k) }}" class="{{ request()->is('admin/kitchen/'.$k->id) ? 'on' : '' }}">Kitchen · {{ $k->name }}</a>
      @endforeach
    @endif
    <div class="who" style="margin-top:auto;padding:14px 8px 0;border-top:1px solid var(--line);font-size:13px">
      <b>{{ $u->name }}</b><br><span class="muted">{{ $u->email }}</span>
      <form method="post" action="{{ route('logout') }}" style="margin-top:8px">@csrf<button class="btn ghost small">Log out</button></form>
    </div>
  </nav>
  <main class="main stagger">
    {{-- Top bar: POS is one tap away on every staff page --}}
    <header class="topbar" role="banner">
      <div class="topbar-title">@yield('title', 'Staff')</div>
      @if($u->hasPermission('pos.use'))
        <div class="pos-launch">
          <a href="{{ $posKitchen ? route('admin.pos', $posKitchen) : route('admin.pos.open') }}" class="btn pos-btn {{ request()->routeIs('admin.pos*') ? 'on' : '' }}" aria-label="Open POS">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M7 20h10M12 16v4M7 8h4M7 12h2"/></svg>
            POS{{ $posKitchen ? ' · '.$posKitchen->name : '' }}
          </a>
          @if($myKitchens->count() > 1)
            <details class="pos-pick">
              <summary aria-label="Choose kitchen for POS">▾</summary>
              <div class="pos-menu" role="menu">
                <div class="small muted" style="padding:6px 10px">Open POS for</div>
                @foreach($myKitchens as $k)
                  <a role="menuitem" href="{{ route('admin.pos', $k) }}" class="{{ $posKitchen?->id === $k->id ? 'on' : '' }}">{{ $k->name }} <span class="muted small">{{ $k->code }}</span></a>
                @endforeach
              </div>
            </details>
          @endif
        </div>
      @endif
      <div class="topbar-user"><span class="small"><b>{{ $u->name }}</b> · {{ $u->role->name }}</span></div>
    </header>
    @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash bad">{{ $errors->first() }}</div>@endif
    @yield('content')
  </main>
</div>
<script>
  // Close the POS kitchen menu when tapping elsewhere or pressing Escape.
  document.addEventListener('click', function (e) { document.querySelectorAll('details.pos-pick[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('details.pos-pick[open]').forEach(function (d) { d.removeAttribute('open'); }); });
</script>
@stack('scripts')
</body>
</html>
