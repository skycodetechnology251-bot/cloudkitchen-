@extends('layouts.admin')
@section('title', 'Daily menu')
@push('head')
<style>
  .mp-week{display:flex;gap:8px;overflow-x:auto;padding:4px 2px 10px;scrollbar-width:thin}
  .mp-day{flex:0 0 112px;border-radius:16px;padding:10px;background:#fff;border:2px solid var(--line);text-decoration:none;color:var(--ink);display:flex;flex-direction:column;gap:6px}
  .mp-day.on{border-color:var(--acc1);background:var(--acc-soft)}
  .mp-day b{font-size:14px}.mp-day small{font-size:11px;color:var(--muted)}
  .mp-dots{display:flex;gap:4px}.mp-dots i{width:20px;height:20px;border-radius:6px;font-style:normal;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;color:#fff}
  .mp-meals{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:6px 0 16px}
  .mp-meal{border-radius:18px;padding:14px;text-decoration:none;color:var(--ink);background:#fff;border:2px solid var(--line);display:flex;gap:10px;align-items:center}
  .mp-meal.on{color:#fff;border-color:transparent}
  .mp-meal span.e{font-size:26px}
  .mp-meal b{display:block;font-size:16px}.mp-meal small{font-size:12px;opacity:.85}
  .mp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
  .mp-dish{border-radius:18px;background:#fff;border:2px solid var(--line);overflow:hidden;display:flex;flex-direction:column;transition:border-color .15s,box-shadow .15s}
  .mp-dish:has(.mp-on:checked){border-color:#1F8A4C;box-shadow:0 0 0 3px #E2F6EA}
  .mp-photo{height:120px;background:#F3EFE6 center/cover no-repeat;position:relative}
  .mp-photo .tick{position:absolute;top:10px;right:10px}
  .mp-body{padding:12px;display:grid;gap:8px}
  .mp-body .two{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .mp-body label.s{font-size:11px;font-weight:800;color:var(--muted);display:block;margin-bottom:2px}
  .mp-body input[type=number]{min-height:38px;padding:6px 10px}
  .mp-switch{display:flex;align-items:center;gap:8px;font-weight:800;cursor:pointer}
  .mp-switch input{width:22px;height:22px;accent-color:#1F8A4C}
  .mp-hide{display:none!important}
  .mp-bar{position:sticky;bottom:0;z-index:5;margin-top:14px;padding:12px 14px;border-radius:18px;background:rgba(255,255,255,.96);box-shadow:0 -10px 30px -20px rgba(0,0,0,.4);display:flex;flex-wrap:wrap;gap:10px;align-items:center}
</style>
@endpush
@section('content')
@php
  $mealInfo = $meals[$meal];
  $tz = config('dailytable.timezone');
  $d = \Carbon\Carbon::parse($date);
  $onCount = $menu->where('status', 'open')->count();
@endphp
<div class="row between"><div><h1>Daily menu</h1><p class="muted" style="margin:4px 0 0">Choose a day and a meal, tick the dishes, set price and portions, then press Save.</p></div>
  <form method="get" class="row" style="flex-wrap:nowrap">
    <input type="hidden" name="meal" value="{{ $meal }}"><input type="hidden" name="date" value="{{ $date }}">
    @if($kitchens->count() > 1)<label for="mk" class="sr">Branch</label><select id="mk" name="kitchen" onchange="this.form.submit()">@foreach($kitchens as $k)<option value="{{ $k->id }}" @selected($kitchen?->id === $k->id)>{{ $k->name }}</option>@endforeach</select>@endif
  </form>
</div>

@if(! $kitchen)
  <div class="card">Add a branch first (Branches → New branch).</div>
@else
{{-- 1. Day --}}
<section class="card" style="margin-bottom:14px">
  <h2 style="font-size:17px;margin-bottom:8px">1 · Pick a day</h2>
  <div class="mp-week">
    @foreach($week as $day)
      @php $key = $day->toDateString(); $c = $counts[$key] ?? collect(); @endphp
      <a class="mp-day {{ $key === $date ? 'on' : '' }}" href="?kitchen={{ $kitchen->id }}&meal={{ $meal }}&date={{ $key }}" @if($key === $date) aria-current="date" @endif>
        <b>{{ $day->isToday() ? 'Today' : ($day->isTomorrow() ? 'Tomorrow' : $day->format('D')) }}</b>
        <small>{{ $day->format('j M') }}</small>
        <span class="mp-dots">@foreach($meals as $mk => $m)@if($m['enabled'])<i title="{{ $m['label'] }}: {{ $c[$mk] ?? 0 }} dishes" style="background:{{ ($c[$mk] ?? 0) ? $m['color'] : '#D8DFDC' }}">{{ $c[$mk] ?? '–' }}</i>@endif @endforeach</span>
      </a>
    @endforeach
    <form method="get" style="flex:0 0 auto;display:flex;align-items:center"><input type="hidden" name="kitchen" value="{{ $kitchen->id }}"><input type="hidden" name="meal" value="{{ $meal }}"><label for="md" class="sr">Other date</label><input id="md" type="date" name="date" value="{{ $date }}" onchange="this.form.submit()" style="min-height:44px"></form>
  </div>
  <p class="small muted" style="margin:4px 0 0">Coloured numbers = dishes published for each meal ({{ collect($meals)->where('enabled', true)->map(fn ($m) => $m['icon'].' '.$m['label'])->join(' · ') }}).</p>
</section>

{{-- 2. Meal --}}
<h2 style="font-size:17px;margin:0 0 4px">2 · Pick a meal for {{ $d->format('l, j M') }}</h2>
<div class="mp-meals" role="tablist">
  @foreach($meals as $mk => $m)
    @continue(! $m['enabled'])
    @php $n = ($counts[$date] ?? collect())[$mk] ?? 0; @endphp
    <a role="tab" aria-selected="{{ $mk === $meal ? 'true' : 'false' }}" class="mp-meal {{ $mk === $meal ? 'on' : '' }}" href="?kitchen={{ $kitchen->id }}&date={{ $date }}&meal={{ $mk }}" style="{{ $mk === $meal ? 'background:'.$m['color'] : '' }}">
      <span class="e">{{ $m['icon'] }}</span><span><b>{{ $m['label'] }}</b><small>{{ $n ? $n.' dishes published' : 'Not published yet' }}</small></span>
    </a>
  @endforeach
</div>

{{-- 3. Dishes --}}
<form method="post" action="{{ route('admin.menus.store') }}" id="mp-form">@csrf
  <input type="hidden" name="kitchen_id" value="{{ $kitchen->id }}"><input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="meal" value="{{ $meal }}">
  <section class="card">
    <div class="row between" style="gap:10px;margin-bottom:10px">
      <h2 style="font-size:17px;margin:0">3 · Tick the dishes for {{ $mealInfo['icon'] }} {{ $mealInfo['label'] }}</h2>
      <div class="row" style="gap:8px">
        <label for="mp-q" class="sr">Search dishes</label><input id="mp-q" type="search" placeholder="Search dishes" style="min-height:40px;max-width:220px">
        <label class="chip grey" style="cursor:pointer;min-height:40px;padding:0 12px"><input type="checkbox" id="mp-only" style="margin:0 6px 0 0;width:16px;height:16px"> Only ticked</label>
        <a class="btn small" href="{{ route('admin.dishes.create') }}">+ New dish</a>
      </div>
    </div>
    @if($dishes->isEmpty())
      <p class="muted">No dishes yet. <a href="{{ route('admin.dishes.create') }}">Add your first dish</a>.</p>
    @endif
    <div class="mp-grid">
      @foreach($dishes as $dish)
        @php
          $m = $menu->get($dish->id);
          $isOn = $m && $m->status === 'open';
          $suits = in_array($meal, $dish->meal_types ?? [], true);
        @endphp
        <div class="mp-dish" data-name="{{ strtolower($dish->name.' '.$dish->category?->name) }}">
          <div class="mp-photo" style="{{ $dish->image_url ? 'background-image:url('.e($dish->image_url).')' : '' }}">
            @unless($dish->image_url)<div style="height:100%;display:flex;align-items:center;justify-content:center">@include('partials.plate', ['size' => 84])</div>@endunless
            <span class="tick">@if($suits)<span class="chip green" style="font-size:11px">Good for {{ strtolower($mealInfo['label']) }}</span>@endif</span>
          </div>
          <div class="mp-body">
            <label class="mp-switch"><input type="checkbox" class="mp-on" name="rows[{{ $dish->id }}][on]" value="1" @checked($isOn)> {{ $dish->name }}</label>
            <div class="row" style="gap:6px">
              <span class="chip {{ ['veg' => 'green', 'egg' => 'amber', 'nonveg' => 'coral'][$dish->diet] ?? 'grey' }}" style="font-size:11px">{{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$dish->diet] ?? $dish->diet }}</span>
              @if($dish->kcal)<span class="chip grey" style="font-size:11px">{{ $dish->kcal }} kcal</span>@endif
              @unless($dish->isReviewed())<span class="chip amber" style="font-size:11px">Dietitian review pending</span>@endunless
            </div>
            <div class="two">
              <div><label class="s" for="p{{ $dish->id }}">Price ₹</label><input id="p{{ $dish->id }}" type="number" step="0.5" min="1" name="rows[{{ $dish->id }}][price]" value="{{ number_format(($m->price_paise ?? $dish->price_paise) / 100, 2, '.', '') }}"></div>
              <div><label class="s" for="c{{ $dish->id }}">Portions</label><input id="c{{ $dish->id }}" type="number" min="0" name="rows[{{ $dish->id }}][capacity]" value="{{ $m->capacity ?? 50 }}"></div>
            </div>
            @if($m && ($m->sold || $m->reserved))<span class="small muted">{{ $m->sold }} sold · {{ $m->reserved }} in baskets</span>@endif
          </div>
        </div>
      @endforeach
    </div>
  </section>

  <div class="mp-bar">
    <label for="cutoff" style="font-weight:800">Orders close at</label>
    <input id="cutoff" type="time" name="cutoff" value="{{ substr($menu->first()?->cutoff_at ?? $mealInfo['cutoff'].':00', 0, 5) }}" style="max-width:130px;min-height:42px">
    <span class="small muted">Delivery {{ collect($mealInfo['slots'])->map(fn ($s) => str_replace('-', '–', $s))->join(' or ') }}</span>
    <span style="margin-left:auto" class="small" id="mp-count"></span>
    <button class="btn">Save {{ $mealInfo['label'] }} for {{ $d->isTomorrow() ? 'tomorrow' : $d->format('D j M') }}</button>
  </div>
</form>

{{-- Copy --}}
<section class="card" style="margin-top:14px">
  <h2 style="font-size:17px;margin-bottom:8px">Quick copy</h2>
  <div class="row" style="gap:8px">
    @foreach([[$d->copy()->subDay(), 'Same meal yesterday'], [$d->copy()->subWeek(), 'Same day last week']] as [$from, $label])
      <form method="post" action="{{ route('admin.menus.copy') }}">@csrf
        <input type="hidden" name="kitchen_id" value="{{ $kitchen->id }}"><input type="hidden" name="to" value="{{ $date }}"><input type="hidden" name="meal" value="{{ $meal }}"><input type="hidden" name="from" value="{{ $from->toDateString() }}">
        <button class="btn ghost small">↺ {{ $label }} ({{ $from->format('D j M') }})</button>
      </form>
    @endforeach
    <form method="post" action="{{ route('admin.menus.copy') }}" class="row" style="gap:6px">@csrf
      <input type="hidden" name="kitchen_id" value="{{ $kitchen->id }}"><input type="hidden" name="to" value="{{ $date }}"><input type="hidden" name="meal" value="{{ $meal }}">
      <label for="cf" class="small" style="font-weight:800">Or copy from</label><input id="cf" type="date" name="from" required style="max-width:170px;min-height:40px">
      <select name="from_meal" aria-label="Meal to copy from" style="max-width:150px;min-height:40px">@foreach($meals as $mk => $m)<option value="{{ $mk }}" @selected($mk === $meal)>{{ $m['label'] }}</option>@endforeach</select>
      <button class="btn ghost small">Copy</button>
    </form>
  </div>
</section>

{{-- Meal times --}}
@if(auth()->user()->seesAllKitchens())
<details class="card" style="margin-top:14px">
  <summary style="cursor:pointer;font-weight:800;font-size:16px">⚙️ Meal times — which meals you serve, order cut-off and delivery windows</summary>
  <form method="post" action="{{ route('admin.menus.meal-times') }}" style="margin-top:12px">@csrf
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:12px">
      @foreach($meals as $mk => $m)
        <div class="soft" style="background:{{ $m['color'] }}14;border:1px solid {{ $m['color'] }}40">
          <label class="row" style="font-weight:800;gap:8px"><input type="checkbox" name="meals[{{ $mk }}][enabled]" value="1" @checked($m['enabled']) @disabled($mk === 'lunch')> {{ $m['icon'] }} {{ $m['label'] }}</label>
          @if($mk === 'lunch')<input type="hidden" name="meals[lunch][enabled]" value="1">@endif
          <div class="field" style="margin:8px 0 0"><label for="co{{ $mk }}">Orders close at (same day)</label><input id="co{{ $mk }}" type="time" name="meals[{{ $mk }}][cutoff]" value="{{ $m['cutoff'] }}"></div>
          <div class="field" style="margin:8px 0 0"><label for="sl{{ $mk }}">Delivery windows</label><input id="sl{{ $mk }}" name="meals[{{ $mk }}][slots]" value="{{ implode(', ', $m['slots']) }}" placeholder="12:00-13:00, 13:00-14:00"></div>
        </div>
      @endforeach
    </div>
    <p class="small muted">Delivery windows are 24-hour times separated by commas, e.g. <code>07:30-08:30, 08:30-09:30</code>. Switched-off meals are hidden in the app.</p>
    <button class="btn small">Save meal times</button>
  </form>
</details>
@endif
@endif
@endsection
@push('scripts')
<script>
(function () {
  var q = document.getElementById('mp-q'), only = document.getElementById('mp-only'), out = document.getElementById('mp-count');
  if (!q) return;
  function apply() {
    var t = q.value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('.mp-dish').forEach(function (c) {
      var on = c.querySelector('.mp-on').checked; if (on) n++;
      c.classList.toggle('mp-hide', (t && c.dataset.name.indexOf(t) < 0) || (only.checked && !on));
    });
    out.textContent = n + (n === 1 ? ' dish ticked' : ' dishes ticked');
  }
  q.addEventListener('input', apply); only.addEventListener('change', apply);
  document.getElementById('mp-form').addEventListener('change', apply);
  apply();
})();
</script>
@endpush
