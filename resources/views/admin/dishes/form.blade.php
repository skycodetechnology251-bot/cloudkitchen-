@extends('layouts.admin')
@section('title', $dish->exists ? 'Edit '.$dish->name : 'Add a dish')
@push('head')
<style>
  .df{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:18px;align-items:start}
  @media (max-width:1050px){.df{grid-template-columns:1fr}}
  .df-step{margin-bottom:16px}
  .df-step h2{font-size:18px;display:flex;align-items:center;gap:10px;margin:0 0 4px}
  .df-step h2 .n{width:30px;height:30px;border-radius:10px;display:grid;place-items:center;color:#fff;font-size:15px}
  .df-photo{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
  .df-img{width:170px;height:170px;border-radius:50%;background:#F3EFE6 center/cover no-repeat;border:4px solid #fff;box-shadow:0 14px 30px -18px rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 170px}
  .df-drop{flex:1;min-width:220px;border:2px dashed #E7C9B8;border-radius:18px;padding:16px;background:#FFF8F4;cursor:pointer;text-align:center}
  .df-drop:hover{border-color:var(--acc1)}
  .df-big{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .df-pick{position:relative;border:2px solid var(--line);border-radius:16px;padding:14px 10px;text-align:center;cursor:pointer;background:#fff;font-weight:800}
  .df-pick input{position:absolute;opacity:0;pointer-events:none}
  .df-pick .e{display:block;font-size:26px;margin-bottom:4px}
  .df-pick:has(input:checked){border-color:var(--pc,#1F8A4C);background:color-mix(in srgb,var(--pc,#1F8A4C) 10%,#fff);box-shadow:0 0 0 3px color-mix(in srgb,var(--pc,#1F8A4C) 18%,transparent)}
  .df-pick:has(input:focus-visible){outline:3px solid var(--acc2);outline-offset:2px}
  .df-meals{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  @media (max-width:640px){.df-meals{grid-template-columns:repeat(2,1fr)}}
  .df-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px}
  .df-chips{display:flex;flex-wrap:wrap;gap:8px}
  .df-chip{border:1.5px solid var(--line);border-radius:999px;padding:7px 12px;cursor:pointer;font-weight:700;font-size:13px;background:#fff}
  .df-chip input{display:none}
  .df-chip:has(input:checked){border-color:#C9472A;background:#FFEDE6;color:#8A2E17}
  .df-slider{display:grid;grid-template-columns:90px 1fr 44px;gap:10px;align-items:center}
  .df-slider input{min-height:auto;padding:0}
  .df-prev{position:sticky;top:16px}
  .df-card{border-radius:22px;background:#fff;border:1px solid var(--line);overflow:hidden}
  .df-card .ph{height:180px;background:#F3EFE6 center/cover no-repeat;display:flex;align-items:center;justify-content:center}
  .df-err{color:var(--bad-ink);font-weight:700;font-size:13px;margin-top:4px}
  details.df-more summary{cursor:pointer;font-weight:800;font-size:16px}
</style>
@endpush
@section('content')
@php
  $v = fn ($k, $d = null) => old($k, $dish->{$k} ?? $d);
  $meal = (array) old('meal_types', $dish->meal_types ?? []);
  $alls = (array) old('allergens', $dish->allergens ?? []);
  $bal = $dish->balance ?: ['grains' => 35, 'protein' => 25, 'veg' => 30, 'dairy' => 10];
  $ingredients = implode(', ', (array) ($dish->ingredients ?? []));
  $canReview = auth()->user()->hasPermission('menu.nutrition');
@endphp
<div class="row between"><div><h1>{{ $dish->exists ? 'Edit '.$dish->name : 'Add a dish' }}</h1><p class="muted" style="margin:4px 0 0">Only the first three steps are needed. Nutrition can be added later or by the dietitian.</p></div>
  <a class="btn ghost" href="{{ route('admin.dishes.index') }}">Back to dishes</a></div>

<form method="post" enctype="multipart/form-data" action="{{ $dish->exists ? route('admin.dishes.update', $dish) : route('admin.dishes.store') }}" id="df">
  @csrf @if($dish->exists) @method('PUT') @endif
  <input type="hidden" name="is_active" value="0">
  <div class="df">
    <div>
      <section class="card df-step">
        <h2><span class="n t-coral">1</span> Photo &amp; name</h2>
        <p class="small muted" style="margin:0 0 12px">A bright, top-down photo of the plate works best (square, up to 4 MB). {{ \App\Support\GoogleDrive::connected() ? 'Saved to your Google Drive.' : '' }}</p>
        <div class="df-photo">
          <div class="df-img" id="df-img" style="{{ $dish->image_url ? 'background-image:url('.e($dish->image_url).')' : '' }}">@unless($dish->image_url)@include('partials.plate', ['size' => 150])@endunless</div>
          <label class="df-drop" for="image">
            <b style="display:block;font-size:15px">📷 {{ $dish->image_url ? 'Change photo' : 'Add a photo' }}</b>
            <span class="small muted">Tap to choose from your computer or phone</span>
            <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" style="display:none">
          </label>
        </div>
        @error('image')<div class="df-err">{{ $message }}</div>@enderror
        <div class="grid g2" style="gap:12px;margin-top:14px">
          <div class="field" style="margin:0"><label for="name">Dish name</label><input id="name" name="name" required maxlength="80" value="{{ $v('name') }}" placeholder="e.g. Millet pongal bowl">@error('name')<div class="df-err">{{ $message }}</div>@enderror</div>
          <div class="field" style="margin:0"><label for="category_id">Category</label><select id="category_id" name="category_id" required>@foreach($fields['category_id']['options'] as $id => $label)<option value="{{ $id }}" @selected((int) $v('category_id') === (int) $id)>{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="field" style="margin:12px 0 0"><label for="description">Short description <span class="muted small">(what customers see)</span></label><textarea id="description" name="description" maxlength="300" rows="2" placeholder="e.g. Foxtail millet and moong dal pongal with coconut chutney">{{ $v('description') }}</textarea></div>
      </section>

      <section class="card df-step">
        <h2><span class="n t-green">2</span> Food type &amp; price</h2>
        <div class="df-big" role="radiogroup" aria-label="Food type" style="margin-top:10px">
          @foreach([['veg', 'Veg', '🥦', '#1F8A4C'], ['egg', 'Egg', '🥚', '#E0A100'], ['nonveg', 'Non-veg', '🍗', '#C9472A']] as [$k, $l, $e, $c])
            <label class="df-pick" style="--pc:{{ $c }}"><input type="radio" name="diet" value="{{ $k }}" @checked($v('diet', 'veg') === $k)><span class="e">{{ $e }}</span>{{ $l }}</label>
          @endforeach
        </div>
        <div class="grid g2" style="gap:12px;margin-top:14px">
          <div class="field" style="margin:0"><label for="price">Price (₹, before GST)</label><input id="price" name="price_paise" type="number" step="0.5" min="1" required value="{{ old('price_paise', $dish->price_paise ? $dish->price_paise / 100 : '') }}" placeholder="120">@error('price_paise')<div class="df-err">{{ $message }}</div>@enderror</div>
          <label class="row" style="font-weight:800;gap:8px;align-self:end;min-height:44px"><input type="checkbox" name="is_active" value="1" @checked($v('is_active', true))> Available to put on menus</label>
        </div>
      </section>

      <section class="card df-step">
        <h2><span class="n t-amber">3</span> Good for which meals?</h2>
        <p class="small muted" style="margin:0 0 10px">These dishes are shown first when you plan that meal. You can still put any dish on any meal.</p>
        <div class="df-meals">
          @foreach($meals as $k => $m)
            <label class="df-pick" style="--pc:{{ $m['color'] }}"><input type="checkbox" name="meal_types[]" value="{{ $k }}" @checked(in_array($k, $meal, true))><span class="e">{{ $m['icon'] }}</span>{{ $m['label'] }}</label>
          @endforeach
        </div>
      </section>

      <details class="card df-step df-more" @if($errors->hasAny(['kcal', 'protein_g', 'carbs_g', 'fat_g', 'fibre_g', 'balance', 'ingredients'])) open @endif>
        <summary>4 · Nutrition, ingredients &amp; allergens <span class="muted small">(optional — the dietitian can fill this)</span></summary>
        <div class="df-grid" style="margin-top:12px">
          @foreach([['kcal', 'Calories (kcal)'], ['protein_g', 'Protein (g)'], ['carbs_g', 'Carbs (g)'], ['fat_g', 'Fat (g)'], ['fibre_g', 'Fibre (g)']] as [$k, $l])
            <div class="field" style="margin:0"><label for="{{ $k }}">{{ $l }}</label><input id="{{ $k }}" name="{{ $k }}" type="number" min="0" value="{{ $v($k) }}"></div>
          @endforeach
          <div class="field" style="margin:0"><label for="sodium">Salt level</label><select id="sodium" name="sodium">@foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $k => $l)<option value="{{ $k }}" @selected($v('sodium', 'medium') === $k)>{{ $l }}</option>@endforeach</select></div>
        </div>
        <div class="field" style="margin:14px 0 0"><label for="ing">Ingredients <span class="muted small">(separate with commas)</span></label><input id="ing" value="{{ old('ingredients_text', $ingredients) }}" name="ingredients_text" placeholder="Foxtail millet, moong dal, ghee, pepper, cashew"></div>
        <input type="hidden" name="ingredients" id="ing-json" value="{{ json_encode((array) ($dish->ingredients ?? [])) }}">
        <div style="margin-top:14px"><b class="small">Allergens in this dish</b>
          <div class="df-chips" style="margin-top:6px">@foreach($fields['allergens']['options'] as $k => $l)<label class="df-chip"><input type="checkbox" name="allergens[]" value="{{ $k }}" @checked(in_array($k, $alls, true))>{{ ucfirst($l) }}</label>@endforeach</div>
        </div>
        <div style="margin-top:16px"><b class="small">What the plate is made of</b> <span class="small muted">(shown as a colour bar in the app)</span>
          @foreach([['grains', 'Grains', '#E9B949'], ['protein', 'Protein', '#E07A4F'], ['veg', 'Vegetables', '#5DB37E'], ['dairy', 'Dairy', '#9CC7EC']] as [$k, $l, $c])
            <div class="df-slider" style="margin-top:8px"><label for="b-{{ $k }}" class="small" style="font-weight:800">{{ $l }}</label><input id="b-{{ $k }}" type="range" min="0" max="100" step="5" value="{{ (int) ($bal[$k] ?? 0) }}" data-k="{{ $k }}" style="accent-color:{{ $c }}"><span class="small" id="bv-{{ $k }}">{{ (int) ($bal[$k] ?? 0) }}%</span></div>
          @endforeach
          <div id="bal-bar" style="display:flex;height:10px;border-radius:99px;overflow:hidden;margin-top:10px"></div>
        </div>
        <input type="hidden" name="balance" id="bal-json" value="{{ json_encode($bal) }}">
        @if($canReview)
          <div style="margin-top:16px"><b class="small">Reviewed as suitable for <span class="muted">(dietitian)</span></b>
            <div class="df-chips" style="margin-top:6px">@foreach($fields['suitable_for']['options'] as $k => $l)<label class="df-chip"><input type="checkbox" name="suitable_for[]" value="{{ $k }}" @checked(in_array($k, (array) old('suitable_for', $dish->suitable_for ?? []), true))>{{ $l }}</label>@endforeach</div>
          </div>
        @endif
      </details>

      <div class="row" style="gap:10px;position:sticky;bottom:0;padding:14px 0;background:linear-gradient(transparent,var(--bg) 30%)">
        <button class="btn">{{ $dish->exists ? 'Save dish' : 'Add dish' }}</button>
        <a class="btn ghost" href="{{ route('admin.dishes.index') }}">Cancel</a>
        @if(! $canReview)<span class="small muted">New or changed nutrition goes to the dietitian for review.</span>@endif
      </div>
    </div>

    <aside class="df-prev" aria-label="Preview">
      <section class="card">
        <h2 style="font-size:16px;margin-bottom:10px">How customers see it</h2>
        <div class="df-card">
          <div class="ph" id="pv-img" style="{{ $dish->image_url ? 'background-image:url('.e($dish->image_url).')' : '' }}">@unless($dish->image_url)@include('partials.plate', ['size' => 140])@endunless</div>
          <div style="padding:14px;display:grid;gap:6px">
            <b style="font-size:17px" id="pv-name">{{ $dish->name ?: 'Dish name' }}</b>
            <span class="small muted" id="pv-desc">{{ $dish->description ?: 'Short description' }}</span>
            <div class="row" style="gap:6px"><span class="chip" id="pv-diet">Veg</span><span class="chip grey" id="pv-kcal" style="{{ $dish->kcal ? '' : 'display:none' }}">{{ $dish->kcal }} kcal</span></div>
            <div id="pv-bar" style="display:flex;height:6px;border-radius:99px;overflow:hidden;max-width:180px"></div>
            <b style="font-size:20px;color:#1F8A4C" id="pv-price">₹{{ $dish->price_paise ? number_format($dish->price_paise / 100) : '0' }}</b>
          </div>
        </div>
      </section>
    </aside>
  </div>
</form>
@endsection
@push('scripts')
<script>
(function () {
  var f = document.getElementById('df');
  var colors = { grains: '#E9B949', protein: '#E07A4F', veg: '#5DB37E', dairy: '#9CC7EC' };
  var dietTxt = { veg: ['Veg', 'green'], egg: ['Egg', 'amber'], nonveg: ['Non-veg', 'coral'] };
  function bars() {
    var b = {}, html = '';
    document.querySelectorAll('[data-k]').forEach(function (s) { b[s.dataset.k] = +s.value; document.getElementById('bv-' + s.dataset.k).textContent = s.value + '%'; });
    Object.keys(colors).forEach(function (k) { if (b[k]) html += '<span style="flex:' + b[k] + ';background:' + colors[k] + '"></span>'; });
    document.getElementById('bal-bar').innerHTML = html; document.getElementById('pv-bar').innerHTML = html;
    document.getElementById('bal-json').value = JSON.stringify(b);
  }
  function preview() {
    document.getElementById('pv-name').textContent = f.name.value || 'Dish name';
    document.getElementById('pv-desc').textContent = f.description.value || 'Short description';
    document.getElementById('pv-price').textContent = '₹' + (f.price_paise.value || 0);
    var d = (f.querySelector('input[name=diet]:checked') || {}).value || 'veg', t = dietTxt[d];
    var el = document.getElementById('pv-diet'); el.textContent = t[0]; el.className = 'chip ' + t[1];
    var k = document.getElementById('pv-kcal'); k.style.display = f.kcal.value ? '' : 'none'; k.textContent = f.kcal.value + ' kcal';
    var ing = document.getElementById('ing').value.split(',').map(function (x) { return x.trim(); }).filter(Boolean);
    document.getElementById('ing-json').value = JSON.stringify(ing);
  }
  document.getElementById('image').addEventListener('change', function () {
    var file = this.files && this.files[0]; if (!file) return;
    var r = new FileReader();
    r.onload = function (e) {
      ['df-img', 'pv-img'].forEach(function (id) { var el = document.getElementById(id); el.innerHTML = ''; el.style.backgroundImage = 'url(' + e.target.result + ')'; });
    };
    r.readAsDataURL(file);
  });
  f.addEventListener('input', function () { bars(); preview(); });
  f.addEventListener('change', function () { bars(); preview(); });
  bars(); preview();
})();
</script>
@endpush
