@extends('layouts.admin')
@section('title', 'POS')
@section('content')
<h1 style="font-size:28px;margin-bottom:6px">Open the POS</h1>
@if($kitchens->isEmpty())
  <div class="card" style="max-width:560px">
    <h2 style="font-size:18px">You're not assigned to a kitchen yet</h2>
    <p class="muted">The POS bills orders for one kitchen at a time. Ask an admin to add your kitchen in <b>Admin → Staff → {{ auth()->user()->name }} → Kitchens they work in</b>.</p>
  </div>
@else
  <p class="muted" style="margin-bottom:18px">Choose the kitchen counter you're billing for. You can switch any time from the POS button in the top bar.</p>
  <div class="grid g2">
    @foreach($kitchens as $k)
      <a href="{{ route('admin.pos', $k) }}" class="card lift" style="text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:6px">
        <span class="chip" style="align-self:flex-start">{{ $k->code }}</span>
        <b style="font-size:18px">{{ $k->name }}</b>
        <span class="small muted">{{ $k->address }}</span>
        <span class="small" style="color:{{ $k->is_open ? 'var(--teal)' : 'var(--bad-ink)' }};font-weight:700">{{ $k->is_open ? 'Open' : 'Closed' }}</span>
        <span class="btn small" style="align-self:flex-start;margin-top:6px">Open POS</span>
      </a>
    @endforeach
  </div>
@endif
@endsection
