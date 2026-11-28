@extends('layouts.admin')
@section('title', 'Review '.$dish->name)
@push('head')
<style>
  .rv{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:18px;align-items:start}
  @media (max-width:1000px){.rv{grid-template-columns:1fr}}
  .rv-nums{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px}
  .rv-chips{display:flex;flex-wrap:wrap;gap:8px}
  .rv-chip{border:1.5px solid var(--line);border-radius:999px;padding:8px 12px;cursor:pointer;font-weight:700;font-size:13px;background:#fff}
  .rv-chip input{display:none}.rv-chip:has(input:checked){border-color:#C9472A;background:#FFEDE6;color:#8A2E17}
  .rv-need{display:flex;gap:12px;align-items:flex-start;padding:12px;border-radius:16px;border:1.5px solid var(--line);background:#fff;cursor:pointer}
  .rv-need input{width:22px;height:22px;margin-top:2px;accent-color:#1F8A4C}
  .rv-need:has(input:checked){border-color:#1F8A4C;background:#E2F6EA}
  .rv-seg{display:flex;gap:6px}.rv-seg label{flex:1;text-align:center;border:1.5px solid var(--line);border-radius:12px;padding:10px;font-weight:800;cursor:pointer;background:#fff}
  .rv-seg input{display:none}.rv-seg label:has(input:checked){border-color:var(--acc1);background:var(--acc-soft)}
</style>
@endpush
@section('content')
<div class="row between"><div><h1>Review: {{ $dish->name }}</h1><p class="muted" style="margin:4px 0 0">Check the numbers and ingredients, tick the health needs this dish suits, then press Approve.</p></div>
  <a class="btn ghost" href="{{ route('admin.dietitian') }}">Back to the desk</a></div>

<form method="post" action="{{ route('admin.dietitian.save', $dish) }}">@csrf
<div class="rv">
  <div>
    <section class="card" style="margin-bottom:16px">
      <h2 style="font-size:18px;margin-bottom:10px">1 · Nutrition per serving</h2>
      <div class="rv-nums">
        @foreach([['kcal', 'Calories (kcal)'], ['protein_g', 'Protein (g)'], ['carbs_g', 'Carbs (g)'], ['fat_g', 'Fat (g)'], ['fibre_g', 'Fibre (g)']] as [$k, $l])
          <div class="field" style="margin:0"><label for="{{ $k }}">{{ $l }}</label><input id="{{ $k }}" name="{{ $k }}" type="number" min="0" value="{{ old($k, $dish->{$k}) }}"></div>
        @endforeach
      </div>
      <div style="margin-top:12px"><b class="small">Salt level</b>
        <div class="rv-seg" style="margin-top:6px">@foreach(['low' => '🟢 Low', 'medium' => '🟡 Medium', 'high' => '🔴 High'] as $k => $l)<label><input type="radio" name="sodium" value="{{ $k }}" @checked(old('sodium', $dish->sodium ?? 'medium') === $k)>{{ $l }}</label>@endforeach</div>
      </div>
    </section>
    <section class="card" style="margin-bottom:16px">
      <h2 style="font-size:18px;margin-bottom:10px">2 · Ingredients &amp; allergens</h2>
      <div class="field" style="margin:0"><label for="ing">Ingredients (separate with commas)</label><textarea id="ing" name="ingredients_text" rows="3">{{ old('ingredients_text', implode(', ', (array) ($dish->ingredients ?? []))) }}</textarea></div>
      <b class="small" style="display:block;margin:12px 0 6px">Allergens present</b>
      <div class="rv-chips">@foreach($allergens as $a)<label class="rv-chip"><input type="checkbox" name="allergens[]" value="{{ $a }}" @checked(in_array($a, (array) old('allergens', $dish->allergens ?? []), true))>{{ ucfirst($a) }}</label>@endforeach</div>
    </section>
    <section class="card">
      <h2 style="font-size:18px;margin-bottom:4px">3 · Suitable for these health needs</h2>
      <p class="small muted" style="margin:0 0 10px">Tick only what you are comfortable recommending. The short note is a reminder, not a rule.</p>
      <div style="display:grid;gap:8px">
        @foreach($conditions as $c => $hint)
          <label class="rv-need"><input type="checkbox" name="suitable_for[]" value="{{ $c }}" @checked(in_array($c, (array) old('suitable_for', $dish->suitable_for ?? []), true))><span><b>{{ $c }}</b><br><span class="small muted">{{ $hint }}</span></span></label>
        @endforeach
      </div>
    </section>
  </div>
  <aside style="position:sticky;top:16px">
    <section class="card" style="text-align:center">
      <div style="width:170px;height:170px;margin:0 auto 10px;border-radius:50%;background:#F3EFE6 center/cover no-repeat;{{ $dish->image_url ? 'background-image:url('.e($dish->image_url).')' : '' }};display:flex;align-items:center;justify-content:center;overflow:hidden">@unless($dish->image_url)@include('partials.plate', ['size' => 160])@endunless</div>
      <b style="font-size:19px">{{ $dish->name }}</b>
      <p class="small muted">{{ $dish->category?->name }} · {{ ['veg' => 'Veg', 'egg' => 'Egg', 'nonveg' => 'Non-veg'][$dish->diet] ?? '' }} · ₹{{ number_format($dish->price_paise / 100) }}</p>
      @if($dish->description)<p class="small">{{ $dish->description }}</p>@endif
      <p>@if($dish->reviewed_at)<span class="chip green">Approved {{ $dish->reviewed_at->diffForHumans() }}</span>@else<span class="chip amber">Waiting for review</span>@endif</p>
      <div style="display:grid;gap:8px;margin-top:10px">
        <button class="btn" name="action" value="approve" style="background:linear-gradient(135deg,#3CC184,#167D49)">✓ Approve</button>
        <label class="row small" style="justify-content:center;gap:6px;font-weight:700"><input type="checkbox" name="next" value="1" checked style="width:16px;height:16px"> Then open the next dish</label>
        <button class="btn ghost" name="action" value="save">Save, approve later</button>
      </div>
    </section>
  </aside>
</div>
</form>
@endsection
