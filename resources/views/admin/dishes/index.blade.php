@extends('layouts.admin')
@section('title', 'Dishes')
@push('head')
<style>
  .dl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
  .dl-card{border-radius:20px;background:#fff;border:1px solid var(--line);overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
  .dl-card:hover{transform:translateY(-3px);box-shadow:0 18px 36px -26px rgba(0,0,0,.5)}
  .dl-card.off{opacity:.6}
  .dl-ph{height:150px;background:#F3EFE6 center/cover no-repeat;display:flex;align-items:center;justify-content:center;position:relative}
  .dl-ph .chip{position:absolute;top:10px;left:10px}
  .dl-b{padding:12px 14px;display:grid;gap:6px;flex:1}
  .dl-b b{font-size:16px}
  .dl-meals{display:flex;gap:4px}
  .dl-meals i{font-style:normal;width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px}
  .dl-filter{display:flex;flex-wrap:wrap;gap:8px}
  .dl-filter button{border:1.5px solid var(--line);background:#fff;border-radius:999px;padding:8px 14px;font-weight:800;cursor:pointer;font-family:inherit;font-size:14px}
  .dl-filter button.on{background:var(--acc1);color:#fff;border-color:transparent}
</style>
@endpush
@section('content')
<div class="row between"><div><h1>Dishes</h1><p class="muted" style="margin:4px 0 0">{{ $dishes->count() }} dishes. Add a dish once, then put it on any day's breakfast, lunch, break time or dinner menu.</p></div>
  <div class="row"><a class="btn" href="{{ route('admin.dishes.create') }}">+ Add a dish</a><a class="btn ghost" href="{{ route('admin.menus.index') }}">Plan menus</a></div></div>

<div class="row between" style="margin-bottom:14px;gap:10px">
  <div class="dl-filter" role="group" aria-label="Filter by meal">
    <button type="button" class="on" data-meal="">All</button>
    @foreach($meals as $k => $m)<button type="button" data-meal="{{ $k }}">{{ $m['icon'] }} {{ $m['label'] }}</button>@endforeach
    <button type="button" data-meal="review">🩺 Needs review</button>
  </div>
  <label for="dl-q" class="sr">Search</label><input id="dl-q" type="search" placeholder="Search dishes" style="max-width:260px">
</div>

@if($dishes->isEmpty())
  <div class="card" style="text-align:center;padding:40px"><div style="font-size:46px">🍲</div><h2 style="font-size:20px">No dishes yet</h2><p class="muted">Add your first dish with a photo, price and the meals it suits.</p><a class="btn" href="{{ route('admin.dishes.create') }}">+ Add a dish</a></div>
@endif
<div class="dl-grid" id="dl">
  @foreach($dishes as $d)
    <article class="dl-card {{ $d->is_active ? '' : 'off' }}" data-name="{{ strtolower($d->name.' '.$d->category?->name) }}" data-meals="{{ implode(' ', $d->meal_types ?? []) }}" data-review="{{ $d->isReviewed() ? 0 : 1 }}">
      <a href="{{ route('admin.dishes.edit', $d) }}" class="dl-ph" style="{{ $d->image_url ? 'background-image:url('.e($d->image_url).')' : '' }}" aria-label="Edit {{ $d->name }}">
        @unless($d->image_url)@include('partials.plate', ['size' => 100])@endunless
        @unless($d->is_active)<span class="chip grey">Hidden</span>@endunless
      </a>
      <div class="dl-b">
        <b>{{ $d->name }}</b>
        <div class="row" style="gap:6px">
          <span class="chip {{ ['veg' => 'green', 'egg' => 'amber', 'nonveg' => 'coral'][$d->diet] ?? 'grey' }}" style="font-size:11px">{{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$d->diet] ?? $d->diet }}</span>
          <span class="chip grey" style="font-size:11px">{{ $d->category?->name }}</span>
          @if($d->isReviewed())<span class="chip green" style="font-size:11px">✓ Reviewed</span>@else<span class="chip amber" style="font-size:11px">Review pending</span>@endif
        </div>
        <div class="row between" style="margin-top:auto">
          <span class="dl-meals" aria-label="Good for">@foreach($meals as $k => $m)@if(in_array($k, $d->meal_types ?? [], true))<i title="{{ $m['label'] }}" style="background:{{ $m['color'] }}22">{{ $m['icon'] }}</i>@endif @endforeach</span>
          <b style="color:#1F8A4C">₹{{ number_format($d->price_paise / 100) }}</b>
        </div>
        <div class="row" style="gap:6px">
          <a class="btn ghost small" href="{{ route('admin.dishes.edit', $d) }}">Edit</a>
          <form method="post" action="{{ route('admin.dishes.destroy', $d) }}" onsubmit="return confirm('Delete {{ addslashes($d->name) }}? Past orders keep their details.')">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form>
        </div>
      </div>
    </article>
  @endforeach
</div>
@endsection
@push('scripts')
<script>
(function () {
  var meal = '', q = document.getElementById('dl-q');
  function apply() {
    var t = q.value.trim().toLowerCase();
    document.querySelectorAll('#dl .dl-card').forEach(function (c) {
      var ok = (!t || c.dataset.name.indexOf(t) >= 0) && (!meal || (meal === 'review' ? c.dataset.review === '1' : (' ' + c.dataset.meals + ' ').indexOf(' ' + meal + ' ') >= 0));
      c.style.display = ok ? '' : 'none';
    });
  }
  document.querySelectorAll('.dl-filter button').forEach(function (b) {
    b.addEventListener('click', function () { document.querySelectorAll('.dl-filter button').forEach(function (x) { x.classList.remove('on'); }); b.classList.add('on'); meal = b.dataset.meal; apply(); });
  });
  q.addEventListener('input', apply);
})();
</script>
@endpush
