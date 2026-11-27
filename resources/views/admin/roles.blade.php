@extends('layouts.admin')
@section('title', 'Roles & permissions')
@section('content')
<h1 style="font-size:28px;margin-bottom:16px">Roles &amp; permissions</h1>
<div class="row" style="align-items:flex-start">
  <nav class="card" style="flex:0 0 260px;padding:10px" aria-label="Roles">
    @foreach($roles as $r)
      <a href="?role={{ $r->id }}" class="row between" style="padding:10px 12px;border-radius:12px;text-decoration:none;color:var(--ink);{{ $current->id === $r->id ? 'background:var(--mint-2);font-weight:800' : '' }}"><span>{{ $r->name }}</span><span class="small muted">{{ $r->users_count }}</span></a>
    @endforeach
  </nav>
  <form method="post" action="{{ route('admin.roles.update', $current) }}" class="card" style="flex:1 1 520px">@csrf @method('PUT')
    <h2 style="font-size:22px">{{ $current->name }}</h2><p class="muted">{{ $current->description }}</p>
    @if($current->slug === 'admin')<div class="flash">The admin role always has every permission.</div>@endif
    <div class="grid g2">
    @foreach($groups as $group => $perms)
      <fieldset style="border:1px solid var(--line);border-radius:16px;padding:12px 14px"><legend class="small muted" style="font-weight:800;padding:0 6px">{{ strtoupper($group) }}</legend>
        @foreach($perms as $p)
          @php($locked = in_array($p->slug, ['health.view','health.plans']) && ! in_array($current->slug, ['dietitian','doctor']))
          <label class="row between" style="font-weight:600;padding:8px 0;border-bottom:1px solid #EEF3F1;flex-wrap:nowrap">
            <span>{{ $p->name }}@if($p->sensitive)<br><span class="small" style="color:#6B3FA0">Sensitive · logged · needs 2-step</span>@endif @if($locked)<br><span class="small muted">Doctors and dietitians only</span>@endif</span>
            <input type="checkbox" name="permissions[]" value="{{ $p->id }}" @checked($current->slug === 'admin' || $current->permissions->contains($p)) @disabled($locked || $current->slug === 'admin')>
          </label>
        @endforeach
      </fieldset>
    @endforeach
    </div>
    @if($current->slug !== 'admin')<button class="btn" style="margin-top:16px">Save {{ $current->name }}</button>@endif
  </form>
</div>
@endsection
