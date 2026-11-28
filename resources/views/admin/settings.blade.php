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
    @if($g === 'gdrive')
      <p class="small" style="margin:0 0 8px">Redirect address: <code style="user-select:all">{{ $drive['redirect'] }}</code></p>
      <div class="soft {{ $drive['connected'] ? 's-green' : 's-amber' }}" style="padding:12px;margin-bottom:10px">
        @if($drive['connected'])
          <b>✓ Connected</b> <span class="small muted">since {{ $drive['since'] }}</span><br>
          <span class="small">New dish, banner, logo and app-login photos are saved to the “Daily Table photos” folder in your Google Drive.</span>
        @else
          <b>Not connected</b><br><span class="small">Photos are saved on this server (public/uploads) until you connect.</span>
        @endif
      </div>
    @endif
    <div class="row" style="gap:8px">
      <button class="btn small">Save</button>
      @if($g === 'gdrive')
        @if($drive['configured'])<a class="btn small" href="{{ route('admin.gdrive.connect') }}" style="background:linear-gradient(135deg,#34A853,#1A73E8)">{{ $drive['connected'] ? 'Reconnect' : 'Connect Google Drive' }}</a>@endif
        @if($drive['connected'])
          @if($drive['local'])<button class="btn ghost small" form="gd-move">Move {{ $drive['local'] }} existing photos to Drive</button>@endif
          <button class="btn ghost small" form="gd-off" onclick="return confirm('Disconnect Google Drive? Photos already in Drive keep working; new photos will be saved on this server.')">Disconnect</button>
        @endif
      @endif
    </div>
  </form>
@endforeach
  <form id="gd-move" method="post" action="{{ route('admin.gdrive.migrate') }}" hidden>@csrf</form>
  <form id="gd-off" method="post" action="{{ route('admin.gdrive.disconnect') }}" hidden>@csrf</form>
  <div class="card"><h2 style="font-size:18px;margin-bottom:12px">GST</h2>
    <p>Rate <b>{{ $gst['rate_percent'] }}%</b> (CGST {{ $gst['rate_percent']/2 }}% + SGST {{ $gst['rate_percent']/2 }}%) · SAC <b>{{ $gst['sac'] }}</b> · prices {{ $gst['prices_include_tax'] ? 'include' : 'exclude' }} tax · invoice prefix <b>{{ $gst['invoice_prefix'] }}</b></p>
    <p class="small muted">Change in .env (GST_RATE, GST_INCLUSIVE, GST_SAC, INVOICE_PREFIX). GSTIN and FSSAI are set per kitchen. Confirm with your chartered accountant.</p></div>
</div>
@endsection
