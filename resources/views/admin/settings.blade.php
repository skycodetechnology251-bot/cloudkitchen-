@extends('layouts.admin')
@section('title', 'Integrations')
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Integrations</h1><span class="chip grey">Secrets are encrypted and never shown in full</span></div>
<div class="grid g2">
@foreach($groups as $g => $cfg)
  <form method="post" action="{{ route('admin.settings.update', $g) }}" class="card lift">@csrf @method('PUT')
    <h2 style="font-size:18px;margin-bottom:12px">{{ $cfg['label'] }}</h2>
    @foreach($cfg['fields'] as $k => [$label, $secret])
      <div class="field"><label for="{{ $g }}_{{ $k }}">{{ $label }}</label><input id="{{ $g }}_{{ $k }}" name="{{ $k }}" type="{{ $secret ? 'password' : 'text' }}" value="{{ $values[$g][$k] ?? '' }}" autocomplete="off"></div>
    @endforeach
    @if($cfg['note'])<p class="small muted">{{ $cfg['note'] }}</p>@endif
    <button class="btn small">Save</button>
  </form>
@endforeach
  <div class="card"><h2 style="font-size:18px;margin-bottom:12px">GST</h2>
    <p>Rate <b>{{ $gst['rate_percent'] }}%</b> (CGST {{ $gst['rate_percent']/2 }}% + SGST {{ $gst['rate_percent']/2 }}%) · SAC <b>{{ $gst['sac'] }}</b> · prices {{ $gst['prices_include_tax'] ? 'include' : 'exclude' }} tax · invoice prefix <b>{{ $gst['invoice_prefix'] }}</b></p>
    <p class="small muted">Change in .env (GST_RATE, GST_INCLUSIVE, GST_SAC, INVOICE_PREFIX). GSTIN and FSSAI are set per kitchen. Confirm with your chartered accountant.</p></div>
</div>
@endsection
