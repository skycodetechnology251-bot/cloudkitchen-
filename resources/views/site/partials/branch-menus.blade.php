{{-- Each branch's own menu (tabs when there are several), grouped by meal. $branches from SiteController::branchMenus(). --}}
@php $mealInfo = \App\Support\Meals::all(); $uid = 'bm'.substr(md5(json_encode($branches->pluck('kitchen.id'))), 0, 6); @endphp
@if($branches->count() > 1)
  <div class="bm-tabs" role="tablist" aria-label="Choose a branch">
    @foreach($branches as $i => $b)
      <button type="button" role="tab" class="bm-tab {{ $i === 0 ? 'on' : '' }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="{{ $uid }}-{{ $b['kitchen']->id }}" data-target="{{ $uid }}-{{ $b['kitchen']->id }}">🏪 {{ $b['kitchen']->name }}</button>
    @endforeach
  </div>
@endif
@foreach($branches as $i => $b)
  <div class="bm-panel" id="{{ $uid }}-{{ $b['kitchen']->id }}" role="tabpanel" @if($i > 0) hidden @endif>
    <p class="bm-when">📍 {{ $b['kitchen']->name }}{{ $b['kitchen']->city ? ', '.$b['kitchen']->city : '' }} · menu for <b>{{ $b['date']->isToday() ? 'today' : ($b['date']->isTomorrow() ? 'tomorrow' : $b['date']->format('l, j M')) }}</b></p>
    @foreach($b['meals'] as $key => $items)
      @php $m = $mealInfo[$key]; @endphp
      <h3 class="bm-meal" style="--mc:{{ $m['color'] }}">{{ $m['icon'] }} {{ $m['label'] }} <small>delivery {{ str_replace('-', '–', $m['slots'][0] ?? '') }} · order by {{ \Carbon\Carbon::createFromFormat('H:i', $m['cutoff'])->format('g:i A') }}</small></h3>
      <div class="menu">
        @foreach($items as $mi)
          <div class="dish">
            @if($mi->dish->image_url)<img src="{{ $mi->dish->image_url }}" alt="{{ $mi->dish->name }}" width="76" height="76" loading="lazy">@else @include('partials.plate', ['size' => 76]) @endif
            <div style="min-width:0;flex:1">
              <h3>{{ $mi->dish->name }}</h3>
              <div class="meta"><span class="tag {{ $mi->dish->diet }}">{{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$mi->dish->diet] ?? 'Veg' }}</span>@if($mi->dish->kcal)<span class="tag kcal">{{ $mi->dish->kcal }} kcal</span>@endif</div>
              @include('partials.balance', ['dish' => $mi->dish])
            </div>
            <span class="price">₹{{ number_format($mi->price_paise / 100) }}</span>
          </div>
        @endforeach
      </div>
    @endforeach
  </div>
@endforeach
@once
<style>
  .bm-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:22px 0 6px}
  .bm-tab{border:1.5px solid var(--line);background:#fff;border-radius:999px;padding:10px 18px;font:800 15px var(--font-body);cursor:pointer;color:var(--ink)}
  .bm-tab.on{background:var(--leaf);border-color:transparent;color:#fff}
  .bm-when{margin:16px 0 4px;color:var(--soft);font-size:15px}
  .bm-meal{font-family:var(--font-body)!important;font-size:19px!important;font-weight:800;margin:22px 0 12px!important;padding-left:12px;border-left:5px solid var(--mc)}
  .bm-meal small{font-size:13px;font-weight:600;color:var(--soft);margin-left:6px}
</style>
<script>
document.addEventListener('click', function (e) {
  var t = e.target.closest('.bm-tab'); if (!t) return;
  var wrap = t.parentElement;
  wrap.querySelectorAll('.bm-tab').forEach(function (b) { b.classList.toggle('on', b === t); b.setAttribute('aria-selected', b === t ? 'true' : 'false'); });
  wrap.querySelectorAll('.bm-tab').forEach(function (b) { var p = document.getElementById(b.dataset.target); if (p) p.hidden = b !== t; });
});
</script>
@endonce
