@extends('layouts.admin')
@section('title', 'System & updates')
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">System &amp; updates</h1><span class="chip grey">Daily Table {{ $codeVersion }}</span></div>
<div class="grid g2">
  <section class="card lift">
    <h2 style="font-size:18px;margin-bottom:12px">Version</h2>
    <p>Files: <b>{{ $codeVersion }}</b> · Database: <b>{{ $dbVersion ?? '—' }}</b></p>
    <p class="small muted">Installed {{ $installedAt ? \Carbon\Carbon::parse($installedAt)->timezone('Asia/Kolkata')->format('d M Y, g:i A') : '—' }}@if($upgradedAt) · last updated {{ \Carbon\Carbon::parse($upgradedAt)->timezone('Asia/Kolkata')->format('d M Y, g:i A') }}@endif · PHP {{ $php }}</p>
    @if(count($pending))
      <div class="flash" style="margin-top:12px"><b>{{ count($pending) }} database update(s) waiting.</b> New files were uploaded; apply the update to finish.</div>
    @else
      <p class="small" style="color:var(--teal);font-weight:700;margin-top:12px">Database is up to date.</p>
    @endif
    <form method="post" action="{{ route('admin.system.update') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Updating… do not close this page';">@csrf
      <button class="btn" style="margin-top:12px">Back up and update now</button>
    </form>
    <p class="small muted" style="margin-top:10px">Takes a backup of the database first, then adds new tables and columns, new roles and permissions, and refreshes caches. Nothing is deleted. Safe to run any time.</p>
  </section>

  <section class="card lift">
    <h2 style="font-size:18px;margin-bottom:12px">Background tasks</h2>
    @if($cronOk)
      <p style="color:var(--teal);font-weight:700">Cron job is running.</p>
    @else
      <p><b>No cron job detected.</b> Tasks run after page visits instead{{ $webCron ? ' (last run '.\Carbon\Carbon::parse($webCron)->diffForHumans().')' : '' }}. Unpaid reservations are still released, but the 9:30 wallet and 10:00 lunch reminders may be missed.</p>
      <p class="small muted">Add this in your hosting panel → Cron Jobs → every minute (* * * * *):</p>
      <p><code style="word-break:break-all">{{ $cron }}</code></p>
    @endif
  </section>

  <section class="card tablebox" style="grid-column:1/-1">
    <h2 style="font-size:18px;margin-bottom:12px">Database backups</h2>
    <table><thead><tr><th>File</th><th>Size</th><th>Taken</th><th></th></tr></thead><tbody>
      @forelse($backups as $b)
        <tr><td>{{ $b['name'] }}</td><td>{{ number_format($b['size'] / 1048576, 2) }} MB</td><td>{{ \Carbon\Carbon::createFromTimestamp($b['at'])->timezone('Asia/Kolkata')->format('d M Y, g:i A') }}</td><td><a class="btn ghost small" href="{{ route('admin.system.backup', $b['name']) }}">Download</a></td></tr>
      @empty
        <tr><td colspan="4" class="muted">No backups yet. One is taken automatically before every update that changes the database.</td></tr>
      @endforelse
    </tbody></table>
    <p class="small muted" style="margin-top:10px">Backups are kept in <code>storage/app/private/backups</code> and are not reachable from the web. Also use your hosting panel's own backups.</p>
  </section>
</div>
@endsection
