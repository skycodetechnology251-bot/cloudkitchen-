@extends('layouts.admin')
@section('title', $title)
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">{{ $title }}</h1><a class="btn ghost" href="{{ route($route.'.index') }}">Back</a></div>
<form method="post" enctype="multipart/form-data" action="{{ $item->exists ? route($route.'.update', $item) : route($route.'.store') }}" class="card" style="max-width:860px">
  @csrf @if($item->exists) @method('PUT') @endif
  <div class="grid g2" style="gap:0 20px">
  @foreach($fields as $name => $f)
    @continue(isset($f['perm']) && ! auth()->user()->hasPermission($f['perm']))
    @continue(($f['type'] ?? '') === 'embedded')
    @php($type = $f['type'] ?? 'text')
    @php($val = old($name, $f['value'] ?? data_get($item, $f['column'] ?? $name)))
    <div class="field" style="{{ in_array($type, ['textarea','json','map','zone_location','banner_designer','multiselect']) ? 'grid-column:1/-1' : '' }}">
      @if($type === 'checkbox')
        <label class="row" style="font-weight:700"><input type="checkbox" name="{{ $name }}" value="1" @checked($val ?? ($item->exists ? false : true))> {{ $f['label'] }}</label>
      @else
        <label for="f_{{ $name }}">{{ $f['label'] }}</label>
        @switch($type)
          @case('textarea')<textarea id="f_{{ $name }}" name="{{ $name }}">{{ $val }}</textarea>@break
          @case('json')<textarea id="f_{{ $name }}" name="{{ $name }}" style="font-family:monospace">{{ is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val }}</textarea>@break
          @case('number')<input id="f_{{ $name }}" type="number" step="any" name="{{ $name }}" value="{{ $val }}">@break
          @case('money')<input id="f_{{ $name }}" type="number" step="0.01" name="{{ $name }}" value="{{ $val !== null && $val !== '' && ! old($name) ? number_format($val/100, 2, '.', '') : $val }}">@break
          @case('date')<input id="f_{{ $name }}" type="date" name="{{ $name }}" value="{{ $val instanceof \Carbon\CarbonInterface ? $val->toDateString() : $val }}">@break
          @case('datetime')<input id="f_{{ $name }}" type="datetime-local" name="{{ $name }}" value="{{ $val instanceof \Carbon\CarbonInterface ? $val->format('Y-m-d\TH:i') : $val }}">@break
          @case('password')<input id="f_{{ $name }}" type="password" name="{{ $name }}" autocomplete="new-password">@break
          @case('file')<input id="f_{{ $name }}" type="file" name="{{ $name }}">@if($val)<div class="help">Current file: {{ $val }}</div>@endif @break
          @case('select')<select id="f_{{ $name }}" name="{{ $name }}">@foreach($f['options'] as $k => $l)<option value="{{ $k }}" @selected((string) $val === (string) $k)>{{ $l }}</option>@endforeach</select>@break
          @case('multiselect')
            <div class="row">@foreach($f['options'] as $k => $l)<label class="chip grey" style="cursor:pointer;font-size:13px;padding:8px 12px"><input type="checkbox" name="{{ $name }}[]" value="{{ $k }}" @checked(in_array($k, (array) $val))> {{ $l }}</label>@endforeach</div>@break
          @case('map')@include('admin.partials.map', ['name' => $name, 'val' => $val])@break
          @case('zone_location')@include('admin.partials.zone-location', ['name' => $name, 'val' => $val, 'item' => $item])@break
          @case('banner_designer')@include('admin.partials.banner-designer', ['name' => $name, 'val' => $val, 'item' => $item])@break
          @default<input id="f_{{ $name }}" type="text" name="{{ $name }}" value="{{ is_array($val) ? implode(',', $val) : $val }}">
        @endswitch
      @endif
      @if(! empty($f['help']))<div class="help">{{ $f['help'] }}</div>@endif
      @error($name)<div class="error">{{ $message }}</div>@enderror
    </div>
  @endforeach
  </div>
  <button class="btn">Save</button>
</form>
@endsection
