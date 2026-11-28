@extends('layouts.admin')
@section('title', 'Dietitian logins')
@push('head')
<style>
  .da-grid{display:grid;grid-template-columns:minmax(280px,420px) 1fr;gap:18px;align-items:start}
  @media(max-width:900px){.da-grid{grid-template-columns:1fr}}
  .da-form label{display:block;font-weight:600;margin:12px 0 6px}
  .da-form input{width:100%}
  .da-person{display:flex;align-items:center;gap:14px;padding:14px;border-radius:18px;background:#fff;border:1px solid var(--line);flex-wrap:wrap}
  .da-av{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#8CC63F,#1F8A4C);color:#fff;font-weight:800;font-size:20px;display:flex;align-items:center;justify-content:center;flex:0 0 48px}
  .da-steps{margin:0;padding-left:20px;line-height:1.9}
  .da-off{opacity:.55}
</style>
@endpush
@section('content')
<div class="row between">
  <div><h1>Dietitian logins</h1><p class="muted" style="margin:4px 0 0">Give your dietitian their own sign-in. They only see the Dietitian desk: recipes, nutrition and diet plans — no money, orders or staff.</p></div>
  <a class="btn ghost" href="{{ route('admin.dietitian') }}">← Dietitian desk</a>
</div>

<div class="da-grid" style="margin-top:18px">
  <form method="post" action="{{ route('admin.dietitians.store') }}" class="card da-form">
    @csrf
    <h2 style="margin:0">➕ Create a dietitian login</h2>
    <label for="da-name">Full name</label>
    <input id="da-name" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Dr. Priya Raman">
    <label for="da-email">Work email (they sign in with this)</label>
    <input id="da-email" type="email" name="email" value="{{ old('email') }}" required placeholder="priya@wownaturals.in">
    <label for="da-phone">Mobile (optional)</label>
    <input id="da-phone" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="10" placeholder="9876543210">
    <label for="da-pw">Password (at least 10 characters)</label>
    <input id="da-pw" type="password" name="password" required minlength="10" autocomplete="new-password">
    <label for="da-pw2">Type the password again</label>
    <input id="da-pw2" type="password" name="password_confirmation" required minlength="10" autocomplete="new-password">
    <button class="btn" style="margin-top:16px;width:100%">Create login</button>
  </form>

  <div style="display:grid;gap:18px">
    <div class="card">
      <h2 style="margin:0 0 8px">How your dietitian signs in</h2>
      <ol class="da-steps">
        <li>Open <a href="{{ $loginUrl }}" target="_blank" rel="noopener"><b>{{ $loginUrl }}</b></a></li>
        <li>Enter the work email and password you set here.</li>
        <li>They land on the <b>Dietitian desk</b> and can start approving dishes.</li>
      </ol>
      <p class="muted" style="margin:8px 0 0">Share the password with them in person or by phone, and ask them to keep it private.</p>
    </div>

    <div class="card">
      <h2 style="margin:0 0 12px">Dietitians ({{ $people->count() }})</h2>
      @forelse($people as $p)
        <div class="da-person {{ $p->is_active ? '' : 'da-off' }}" style="margin-bottom:10px">
          <div class="da-av">{{ mb_strtoupper(mb_substr($p->name, 0, 1)) }}</div>
          <div style="flex:1;min-width:180px">
            <b>{{ $p->name }}</b> @if(! $p->is_active)<span class="pill">Turned off</span>@endif
            <div class="muted">{{ $p->email }} @if($p->phone) · {{ $p->phone }} @endif</div>
          </div>
          <form method="post" action="{{ route('admin.dietitians.update', $p) }}" style="display:flex;gap:6px;align-items:center">
            @csrf @method('put')
            <input type="hidden" name="action" value="password">
            <input type="password" name="password" minlength="10" required placeholder="New password" autocomplete="new-password" style="width:150px">
            <button class="btn ghost">Reset</button>
          </form>
          <form method="post" action="{{ route('admin.dietitians.update', $p) }}">
            @csrf @method('put')
            <input type="hidden" name="action" value="toggle">
            <button class="btn {{ $p->is_active ? 'ghost' : '' }}">{{ $p->is_active ? 'Turn off' : 'Turn on' }}</button>
          </form>
        </div>
      @empty
        <p class="muted" style="margin:0">No dietitian logins yet. Create one with the form.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
