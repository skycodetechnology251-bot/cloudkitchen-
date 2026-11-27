@extends('layouts.admin')
@section('title', 'Daily menu')
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Daily menu</h1>
  <form method="get" class="row" style="flex-wrap:nowrap">
    <label for="mk" class="sr">Kitchen</label><select id="mk" name="kitchen" onchange="this.form.submit()">@foreach($kitchens as $k)<option value="{{ $k->id }}" @selected($kitchen?->id === $k->id)>{{ $k->name }}</option>@endforeach</select>
    <label for="md" class="sr">Date</label><input id="md" type="date" name="date" value="{{ $date }}" onchange="this.form.submit()">
  </form>
</div>
@if($kitchen)
<form method="post" action="{{ route('admin.menus.store') }}" class="card">@csrf
  <input type="hidden" name="kitchen_id" value="{{ $kitchen->id }}"><input type="hidden" name="date" value="{{ $date }}">
  <div class="row" style="margin-bottom:12px"><label for="cutoff" style="margin:0">Order cut-off</label><input id="cutoff" type="time" name="cutoff" value="{{ substr($menu->first()?->cutoff_at ?? '10:45:00', 0, 5) }}" style="max-width:140px"></div>
  <div class="tablebox"><table>
    <thead><tr><th>On menu</th><th>Dish</th><th>Diet</th><th>Price ₹</th><th>Portions</th><th>Sold / held</th><th>Review</th></tr></thead>
    <tbody>@foreach($dishes as $d)@php($m = $menu->get($d->id))
      <tr><td><input type="checkbox" name="rows[{{ $d->id }}][on]" value="1" @checked($m && $m->status === 'open') aria-label="Put {{ $d->name }} on the menu"></td>
        <td><b>{{ $d->name }}</b></td><td>{{ $d->diet }}</td>
        <td><input type="number" step="0.01" name="rows[{{ $d->id }}][price]" value="{{ number_format(($m->price_paise ?? $d->price_paise)/100, 2, '.', '') }}" style="max-width:110px" aria-label="Price for {{ $d->name }}"></td>
        <td><input type="number" name="rows[{{ $d->id }}][capacity]" value="{{ $m->capacity ?? 50 }}" style="max-width:100px" aria-label="Portions for {{ $d->name }}"></td>
        <td>{{ $m ? $m->sold.' / '.$m->reserved : '—' }}</td>
        <td>@if($d->isReviewed())<span class="chip">Reviewed</span>@else<span class="chip warn">Needs dietitian review</span>@endif</td></tr>
    @endforeach</tbody>
  </table></div>
  <button class="btn" style="margin-top:14px">Publish menu for {{ \Carbon\Carbon::parse($date)->format('D d M') }}</button>
</form>
<form method="post" action="{{ route('admin.menus.copy') }}" class="row card" style="margin-top:16px">@csrf
  <input type="hidden" name="kitchen_id" value="{{ $kitchen->id }}"><input type="hidden" name="to" value="{{ $date }}">
  <label for="from" style="margin:0">Copy menu from</label><input id="from" type="date" name="from" style="max-width:180px"><button class="btn ghost small">Copy to this day</button>
</form>
@else<p class="muted">Add a kitchen first.</p>@endif
@endsection
