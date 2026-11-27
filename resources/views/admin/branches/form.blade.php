@extends('layouts.admin')
@section('title', $branch->exists ? 'Edit '.$branch->name : 'New branch')
@section('content')
@php
  $zoneTone = ['live' => 'green', 'testing' => 'amber', 'draft' => 'grey', 'paused' => 'coral', 'closed' => 'grey'];
  $checkedZones = array_map('intval', old('zone_ids', $mine));
  $checkedStaff = array_map('intval', old('staff_ids', $team));
  $radiusKm = old('radius_km', $branch->service_radius_m ? $branch->service_radius_m / 1000 : 8);
  $newZones = old('new_zones', $branch->exists ? [] : [['name' => '', 'radius' => 3, 'status' => 'testing']]);
  $in = 'width:100%;min-height:44px;border-radius:12px;border:1px solid var(--line);padding:0 12px;background:#fff;font:inherit';
@endphp
@push('head')
<style>
  .bf-sec{margin-bottom:16px}.bf-sec h2{font-size:18px;display:flex;align-items:center;gap:10px;margin:0 0 4px}
  .bf-sec h2 .n{width:28px;height:28px;border-radius:9px;display:grid;place-items:center;color:#fff;font-size:14px}
  .bf-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
  .bf-grid label{display:block;font-size:13px;font-weight:800;margin-bottom:6px;color:#27403A}
  .zpick{display:flex;gap:10px;align-items:flex-start;border:1.5px solid var(--line);border-radius:14px;padding:12px;cursor:pointer;background:#fff}
  .zpick:has(input:checked){border-color:#7A3FF2;background:#F5F0FF}
  .zpick input{margin-top:3px;width:18px;height:18px;accent-color:#7A3FF2}
  .nz{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;align-items:end;background:#F5F0FF;border-radius:14px;padding:10px;margin-top:8px}
  .spick{display:inline-flex;align-items:center;gap:6px;border:1.5px solid var(--line);border-radius:999px;padding:7px 12px;cursor:pointer;background:#fff;font-size:13px;font-weight:700}
  .spick:has(input:checked){border-color:#2554B8;background:#EEF3FF;color:#1D3F8F}
  .bf-err{color:var(--bad-ink);font-size:13px;font-weight:700;margin-top:4px}
  @media (max-width:640px){.nz{grid-template-columns:1fr 1fr}}
</style>
@endpush

<div class="pagehead" style="background:linear-gradient(120deg,#167D49 0%,#0F5C4D 40%,#7A3FF2 100%)">
  <div class="row between" style="align-items:flex-end;gap:16px;position:relative;z-index:1">
    <div>
      <h1>{{ $branch->exists ? 'Edit branch · '.$branch->name : 'New branch' }}</h1>
      <p>Fill in the branch, place it on the map, choose or create the zones it delivers to, and pick its team.</p>
    </div>
    <a class="btn" href="{{ route('admin.branches.index') }}" style="background:rgba(255,255,255,.2)">Back to branches</a>
  </div>
</div>

@if($errors->any())<div class="card soft s-coral" style="margin-bottom:14px;font-weight:700">Please fix the highlighted fields: {{ $errors->first() }}</div>@endif

<form method="post" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}">
  @csrf @if($branch->exists) @method('PUT') @endif

  <section class="card bf-sec">
    <h2><span class="n t-teal">1</span> Branch details</h2>
    <p class="small muted" style="margin:0 0 12px">The code starts this branch's invoice numbers, e.g. ADY/2026-27/00001.</p>
    <div class="bf-grid">
      <div><label for="b-name">Branch name</label><input id="b-name" name="name" required maxlength="80" value="{{ old('name', $branch->name) }}" placeholder="e.g. Adyar kitchen" style="{{ $in }}">@error('name')<div class="bf-err">{{ $message }}</div>@enderror</div>
      <div><label for="b-code">Code (letters)</label><input id="b-code" name="code" required maxlength="8" value="{{ old('code', $branch->code) }}" placeholder="ADY" style="{{ $in }};text-transform:uppercase">@error('code')<div class="bf-err">{{ $message }}</div>@enderror</div>
      <div><label for="b-phone">Phone</label><input id="b-phone" name="phone" maxlength="15" value="{{ old('phone', $branch->phone) }}" placeholder="044 4000 0000" style="{{ $in }}"></div>
      <div><label for="b-legal">Legal name (on invoices)</label><input id="b-legal" name="legal_name" maxlength="120" value="{{ old('legal_name', $branch->legal_name) }}" style="{{ $in }}"></div>
      <div><label for="b-gstin">GSTIN</label><input id="b-gstin" name="gstin" maxlength="15" value="{{ old('gstin', $branch->gstin) }}" placeholder="33ABCDE1234F1Z5" style="{{ $in }};text-transform:uppercase">@error('gstin')<div class="bf-err">Enter a valid 15-character GSTIN.</div>@enderror</div>
      <div><label for="b-fssai">FSSAI licence</label><input id="b-fssai" name="fssai" maxlength="14" inputmode="numeric" value="{{ old('fssai', $branch->fssai) }}" style="{{ $in }}">@error('fssai')<div class="bf-err">{{ $message }}</div>@enderror</div>
    </div>
    <label class="row" style="font-weight:800;margin-top:14px;gap:8px"><input type="checkbox" name="is_open" value="1" @checked(old('is_open', $branch->is_open)) style="width:18px;height:18px"> Open for orders</label>
  </section>

  <section class="card bf-sec">
    <h2><span class="n t-blue">2</span> Where is the branch?</h2>
    <p class="small muted" style="margin:0 0 12px">Choose the country, state and city, type the address, then search it or use your current location if you're at the branch.</p>
    @include('admin.partials.zone-location', ['mode' => 'point', 'name' => 'location', 'val' => null, 'item' => $branch, 'overlays' => $overlays, 'radiusInput' => 'b-radius'])
    @error('address')<div class="bf-err">{{ $message }}</div>@enderror
    <div class="row" style="gap:10px;margin-top:12px;align-items:center">
      <label for="b-radius" style="font-weight:800">Delivers up to</label>
      <input id="b-radius" name="radius_km" type="number" step="0.5" min="0.5" max="50" data-km="1" value="{{ $radiusKm }}" style="{{ $in }};width:110px"> <b>km</b>
      <span class="small muted">from the branch, even inside its zones.</span>
    </div>
    @error('radius_km')<div class="bf-err">{{ $message }}</div>@enderror
  </section>

  <section class="card bf-sec">
    <h2><span class="n t-purple">3</span> Delivery zones</h2>
    <p class="small muted" style="margin:0 0 12px">A branch needs at least one zone. Tick zones it delivers to, or create new ones around the branch pin. You can reshape any zone later in Zones.</p>
    @error('zones')<div class="bf-err" style="margin-bottom:8px">{{ $message }}</div>@enderror
    @if($zones->isNotEmpty())
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(240px,100%),1fr));gap:10px">
        @foreach($zones as $z)
          @php $others = $z->kitchens->where('id', '!=', $branch->id)->pluck('name'); @endphp
          <label class="zpick">
            <input type="checkbox" name="zone_ids[]" value="{{ $z->id }}" @checked(in_array($z->id, $checkedZones))>
            <span><b>{{ $z->name }}</b> <span class="chip {{ $zoneTone[$z->status] ?? 'grey' }}" style="font-size:11px">{{ ucfirst($z->status) }}</span>
              <span class="small muted" style="display:block">{{ collect([$z->city, $z->state])->filter()->join(', ') ?: 'No city set' }}</span>
              @if($others->isNotEmpty())<span class="small" style="display:block;color:#5B3BB0">Also served by {{ $others->join(', ') }}</span>@endif
            </span>
          </label>
        @endforeach
      </div>
    @endif

    <div style="margin-top:14px">
      <b class="small">Create new zones for this branch</b>
      <div id="nz-list">
        @foreach($newZones as $i => $z)
          <div class="nz">
            <div><label class="small" style="font-weight:800">Zone name</label><input name="new_zones[{{ $i }}][name]" maxlength="60" value="{{ $z['name'] ?? '' }}" placeholder="e.g. Thiruvanmiyur" style="{{ $in }}"></div>
            <div><label class="small" style="font-weight:800">Circle size</label><select name="new_zones[{{ $i }}][radius]" style="{{ $in }}">@foreach([1, 1.5, 2, 3, 4, 5, 8, 10] as $km)<option value="{{ $km }}" @selected((float) ($z['radius'] ?? 3) == $km)>{{ $km }} km</option>@endforeach</select></div>
            <div><label class="small" style="font-weight:800">Start as</label><select name="new_zones[{{ $i }}][status]" style="{{ $in }}"><option value="testing" @selected(($z['status'] ?? 'testing') === 'testing')>Testing (takes orders)</option><option value="draft" @selected(($z['status'] ?? '') === 'draft')>Draft (hidden)</option></select></div>
            <button type="button" class="btn ghost small nz-del" aria-label="Remove this new zone">Remove</button>
          </div>
        @endforeach
      </div>
      <button type="button" class="btn ghost small" id="nz-add" style="margin-top:8px">+ Add a new zone</button>
      <p class="small muted" style="margin:6px 0 0">New zones are drawn as a circle around the branch pin. "Testing" zones take orders; approve go-live from Zones when ready.</p>
    </div>
  </section>

  <section class="card bf-sec">
    <h2><span class="n t-coral">4</span> Team at this branch</h2>
    <p class="small muted" style="margin:0 0 12px">Ticked staff can open this branch's POS and kitchen board. Riders ticked here deliver for this branch.</p>
    @forelse($staff as $role => $people)
      <div style="margin-bottom:10px"><div class="small" style="font-weight:800;margin-bottom:6px">{{ $role }}</div>
        <div class="row" style="gap:6px">@foreach($people as $u)<label class="spick"><input type="checkbox" name="staff_ids[]" value="{{ $u->id }}" @checked(in_array($u->id, $checkedStaff))> {{ $u->name }}</label>@endforeach</div></div>
    @empty
      <p class="muted">No staff yet. Add people in Staff, then tick them here.</p>
    @endforelse
  </section>

  <div class="row" style="gap:10px;position:sticky;bottom:0;background:linear-gradient(transparent,var(--bg,#F4F6F5) 30%);padding:16px 0">
    <button class="btn" style="background:linear-gradient(135deg,#34B57A,#167D49);min-width:200px">{{ $branch->exists ? 'Save branch' : 'Create branch' }}</button>
    <a class="btn ghost" href="{{ route('admin.branches.index') }}">Cancel</a>
  </div>
</form>

<template id="nz-tpl">
  <div class="nz">
    <div><label class="small" style="font-weight:800">Zone name</label><input data-n="name" maxlength="60" placeholder="e.g. Thiruvanmiyur" style="{{ $in }}"></div>
    <div><label class="small" style="font-weight:800">Circle size</label><select data-n="radius" style="{{ $in }}">@foreach([1, 1.5, 2, 3, 4, 5, 8, 10] as $km)<option value="{{ $km }}" @selected($km == 3)>{{ $km }} km</option>@endforeach</select></div>
    <div><label class="small" style="font-weight:800">Start as</label><select data-n="status" style="{{ $in }}"><option value="testing">Testing (takes orders)</option><option value="draft">Draft (hidden)</option></select></div>
    <button type="button" class="btn ghost small nz-del" aria-label="Remove this new zone">Remove</button>
  </div>
</template>
@endsection
@push('scripts')
<script>
(function () {
  var list = document.getElementById('nz-list'), n = list.children.length + 100;
  document.getElementById('nz-add').addEventListener('click', function () {
    if (list.children.length >= 10) return;
    var row = document.getElementById('nz-tpl').content.firstElementChild.cloneNode(true), i = n++;
    row.querySelectorAll('[data-n]').forEach(function (el) { el.name = 'new_zones[' + i + '][' + el.dataset.n + ']'; });
    list.appendChild(row); row.querySelector('input').focus();
  });
  list.addEventListener('click', function (e) { if (e.target.classList.contains('nz-del')) e.target.closest('.nz').remove(); });
})();
</script>
@endpush
