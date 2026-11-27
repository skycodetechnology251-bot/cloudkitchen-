<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>@yield('title', 'Staff') · {{ \App\Support\Brand::name() }}</title>
@include('partials.brand-head')
<link rel="stylesheet" href="{{ asset('css/dt.css') }}">
@stack('head')
</head>
<body class="acc-{{ \App\Support\Accent::current() }}">
@php($u = auth()->user())
@php($myKitchens = $u->accessibleKitchens())
@php($posKitchen = request()->route('kitchen') instanceof \App\Models\Kitchen && request()->routeIs('admin.pos') ? request()->route('kitchen') : null)
<div class="staff">
  <nav class="side" aria-label="Staff">
    <div class="brand">@include('partials.brand-mark', ['size' => 38])</div>
    <span class="chip" style="align-self:flex-start;margin:0 8px 12px">{{ strtoupper($u->role->name) }}</span>
    @php($ic = [
      'home' => '<path d="M3 11 12 4l9 7v9h-6v-6H9v6H3z"/>',
      'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.5 0 2-1 2-2s-1-1.5-1-2.5S14 15 15 15h2a4 4 0 0 0 4-4c0-4.4-4-8-9-8Z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10" cy="7" r="1.2"/><circle cx="15" cy="7.5" r="1.2"/>',
      'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>',
      'calendar' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
      'bowl' => '<path d="M4 11h16a8 8 0 0 1-16 0ZM12 3v4M8 5v2M16 5v2"/>',
      'pin' => '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
      'store' => '<path d="M4 10h16l-1-5H5zM5 10v10h14V10M10 20v-5h4v5"/>',
      'image' => '<rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
      'tag' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/>',
      'cart' => '<path d="M3 4h2l2 12h11l2-8H6"/><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/>',
      'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
      'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0M16 4a3.5 3.5 0 0 1 0 7M18 20a7 7 0 0 0-3-5.7"/>',
      'wallet' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M16 12h2M3 10h18"/>',
      'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/>',
      'plug' => '<path d="M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4"/>',
      'cog' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6A7 7 0 0 0 5 12c0 .4 0 .8.1 1.2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z"/>',
      'flame' => '<path d="M12 22c4 0 7-3 7-7 0-5-5-7-5-12-3 2-6 6-6 9-1-1-2-2-2-4-2 2-1 4-1 7 0 4 3 7 7 7Z"/>',
    ])
    @php($groups = [
      ['Daily service', [['admin.dashboard', 'Overview', null, 'home', 'teal'], ['admin.orders.index', 'Orders', 'orders.view', 'receipt', 'blue'], ['admin.menus.index', 'Daily menu', 'menu.publish', 'calendar', 'amber']]],
      ['Catalogue & areas', [['admin.dishes.index', 'Dishes', 'menu.edit', 'bowl', 'coral'], ['admin.zones.index', 'Zones', 'zones.edit', 'pin', 'pink'], ['admin.branches.index', 'Branches', 'zones.edit', 'store', 'green']]],
      ['Marketing', [['admin.banners.index', 'Banners & popups', 'mkt.banners', 'image', 'purple'], ['admin.offers.index', 'Offers', 'mkt.banners', 'tag', 'pink']]],
      ['Money', [['admin.expenses.index', 'Purchases & expenses', 'expenses.edit', 'cart', 'amber'], ['admin.reports', 'Reports', 'fin.reports', 'chart', 'sky'], ['admin.salaries', 'Salaries & payroll', 'staff.salary', 'wallet', 'green']]],
      ['Team & settings', [['admin.staff.index', 'Staff', 'staff.manage', 'users', 'blue'], ['admin.roles.index', 'Roles & permissions', 'staff.roles', 'shield', 'purple'], ['admin.branding', 'Branding', 'settings.manage', 'palette', 'pink'], ['admin.settings.index', 'Integrations', 'settings.manage', 'plug', 'sky'], ['admin.system', 'System & updates', 'settings.manage', 'cog', 'grey']]],
    ])
    @foreach($groups as [$gname, $glinks])
      @php($visible = array_filter($glinks, fn ($l) => ! $l[2] || $u->hasPermission($l[2])))
      @if($visible)
        <div class="grp">{{ $gname }}</div>
        @foreach($visible as [$name, $label, $perm, $icon, $color])
          <a href="{{ route($name) }}" class="{{ request()->routeIs(str_replace('.index', '', $name).'*') ? 'on' : '' }}" @if(request()->routeIs(str_replace('.index', '', $name).'*')) aria-current="page" @endif>
            <span class="ico i-{{ $color }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ic[$icon] !!}</svg></span>{{ $label }}
          </a>
        @endforeach
      @endif
    @endforeach
    @if($u->hasPermission('kitchen.board') && $myKitchens->count())
      <div class="grp">Branch kitchen boards</div>
      @foreach($myKitchens as $k)
        <a href="{{ route('admin.kitchen', $k) }}" class="{{ request()->is('admin/kitchen/'.$k->id) ? 'on' : '' }}"><span class="ico i-coral"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ic['flame'] !!}</svg></span>{{ $k->name }}</a>
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
