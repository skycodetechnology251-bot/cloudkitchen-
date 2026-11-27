@extends('layouts.admin')
@section('title', $title)
@section('content')
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">{{ $title }}</h1>
  <div class="row"><form method="get" class="row" style="flex-wrap:nowrap"><label for="q" class="sr">Search</label><input id="q" name="q" value="{{ request('q') }}" placeholder="Search" style="min-width:220px"></form><a class="btn" href="{{ route($route.'.create') }}">+ New</a></div>
</div>
<div class="card tablebox">
<table>
  <thead><tr>@foreach($columns as $label)<th>{{ $label }}</th>@endforeach<th></th></tr></thead>
  <tbody>
  @forelse($items as $item)
    <tr>
      @foreach($columns as $key => $label)
        @php($v = data_get($item, $key))
        <td>
          @if($loop->first)<b>{{ is_array($v) ? implode(', ', $v) : $v }}</b>
          @elseif(is_bool($v) || in_array($key, ['is_active','is_open']))<span class="chip {{ $v ? 'green' : 'grey' }}">{{ $v ? '● Yes' : 'No' }}</span>
          @elseif(str_ends_with($key, '_paise'))<b style="color:var(--acc-ink)">₹{{ number_format(($v ?? 0)/100, 2) }}</b>
          @elseif(in_array($key, ['diet', 'category', 'role.name']))<span class="chip {{ ['veg' => 'green', 'nonveg' => 'coral', 'egg' => 'amber'][$v] ?? 'blue' }}">{{ is_string($v) ? ucfirst(str_replace('_', ' ', $v)) : $v }}</span>
          @elseif($v instanceof \Carbon\CarbonInterface){{ $v->format('d M Y') }}
          @elseif($key === 'status')<span class="chip {{ ['live' => 'green', 'testing' => 'amber', 'draft' => 'grey', 'paused' => 'coral', 'closed' => 'grey', 'active' => 'green', 'scheduled' => 'blue', 'expired' => 'grey'][$v] ?? 'purple' }}">{{ ucfirst($v) }}</span>
          @else{{ is_array($v) ? implode(', ', $v) : $v }}@endif
        </td>
      @endforeach
      <td class="row" style="justify-content:flex-end;flex-wrap:nowrap">
        @if($route === 'admin.zones' && $item->status !== 'live' && auth()->user()->hasPermission('zones.golive'))
          <form method="post" action="{{ route('admin.zones.golive', $item) }}">@csrf<button class="btn mint small">Approve go-live</button></form>
        @endif
        <a class="btn ghost small" href="{{ route($route.'.edit', $item) }}">Edit</a>
        @if($canDelete)<form method="post" action="{{ route($route.'.destroy', $item) }}" onsubmit="return confirm('Delete this?')">@csrf @method('DELETE')<button class="btn danger small">Delete</button></form>@endif
      </td>
    </tr>
  @empty
    <tr><td colspan="{{ count($columns) + 1 }}" class="muted">Nothing here yet.</td></tr>
  @endforelse
  </tbody>
</table>
</div>
<div style="margin-top:14px">{{ $items->links() }}</div>
@endsection
