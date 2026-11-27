@extends('layouts.admin')
@section('title', 'Reports')
@section('content')
@php($inc = collect($monthly)->sum('income')) @php($exp = collect($monthly)->sum('expense'))
@php($max = max(1, collect($monthly)->max(fn($m) => max($m['income'], $m['expense']))))
<div class="row between" style="margin-bottom:16px"><h1 style="font-size:28px">Reports</h1>
  <form method="get" class="row" style="flex-wrap:nowrap">
    <label for="rk" class="sr">Kitchen</label><select id="rk" name="kitchen"><option value="">All kitchens</option>@foreach($kitchens as $id => $n)<option value="{{ $id }}" @selected($kitchenId == $id)>{{ $n }}</option>@endforeach</select>
    <label for="rf" class="sr">From</label><input id="rf" type="date" name="from" value="{{ $from->toDateString() }}"><label for="rt" class="sr">To</label><input id="rt" type="date" name="to" value="{{ $to->toDateString() }}">
    <button class="btn small">Show</button>@if(auth()->user()->hasPermission('fin.export'))<a class="btn ghost small" href="{{ route('admin.reports.gst', request()->query()) }}">GST register CSV</a>@endif
  </form></div>
<div class="grid g4" style="margin-bottom:18px">
  <div class="card" style="background:var(--teal);color:#fff;border:0"><div class="small">Income (taxable)</div><div style="font-size:28px;font-weight:800">₹{{ number_format($inc/100) }}</div></div>
  <div class="card"><div class="small muted">Expenses</div><div style="font-size:28px;font-weight:800">₹{{ number_format($exp/100) }}</div></div>
  <div class="card"><div class="small muted">Profit</div><div style="font-size:28px;font-weight:800">₹{{ number_format(($inc-$exp)/100) }}</div><div class="small muted">{{ $inc ? round(($inc-$exp)/$inc*100) : 0 }}% margin</div></div>
  <div class="card"><div class="small muted">GST collected</div><div style="font-size:28px;font-weight:800">₹{{ number_format(collect($monthly)->sum(fn($m)=>$m['cgst']+$m['sgst'])/100) }}</div></div>
</div>
<div class="grid g2">
  <section class="card"><h2 style="font-size:17px;margin-bottom:12px">Income vs expenses</h2>
    <div role="img" aria-label="Monthly income and expenses" class="row" style="align-items:flex-end;height:220px;gap:14px;border-bottom:1px solid #D5E2DD;flex-wrap:nowrap">
      @foreach($monthly as $m)<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px"><div class="row" style="align-items:flex-end;gap:4px;height:190px;flex-wrap:nowrap">
        <div class="growy" title="Income ₹{{ number_format($m['income']/100) }}" style="width:20px;border-radius:6px 6px 2px 2px;background:var(--teal);height:{{ round($m['income']/$max*190) }}px"></div>
        <div class="growy" title="Expenses ₹{{ number_format($m['expense']/100) }}" style="width:20px;border-radius:6px 6px 2px 2px;background:#E9B949;height:{{ round($m['expense']/$max*190) }}px"></div></div><span class="small muted">{{ substr($m['label'],0,3) }}</span></div>@endforeach
    </div><p class="small muted">Green income, yellow expenses.</p></section>
  <section class="card"><h2 style="font-size:17px;margin-bottom:12px">Expenses by category</h2>
    @php($cmax = max(1, $categories->max()))
    @forelse($categories as $cat => $v)<div style="margin-bottom:10px"><div class="row between small"><b>{{ ucfirst($cat) }}</b><span>₹{{ number_format($v/100) }}</span></div><div style="height:8px;border-radius:99px;background:#EEF3F1"><div class="grow" style="height:8px;border-radius:99px;background:var(--teal);width:{{ round($v/$cmax*100) }}%"></div></div></div>@empty<p class="muted">No expenses recorded.</p>@endforelse
    <p class="small muted">Owed to customers: wallets ₹{{ number_format($liabilities['wallets']/100) }} · {{ number_format($liabilities['points']) }} reward points</p></section>
</div>
<section class="card tablebox" style="margin-top:16px"><h2 style="font-size:17px">GST by month</h2>
  <table><thead><tr><th>Month</th><th>Taxable</th><th>CGST</th><th>SGST</th><th>Total GST</th></tr></thead><tbody>
  @foreach($monthly as $m)<tr><td><b>{{ $m['label'] }}</b></td><td>₹{{ number_format($m['taxable']/100, 2) }}</td><td>₹{{ number_format($m['cgst']/100, 2) }}</td><td>₹{{ number_format($m['sgst']/100, 2) }}</td><td><b>₹{{ number_format(($m['cgst']+$m['sgst'])/100, 2) }}</b></td></tr>@endforeach
  </tbody></table></section>
<section class="card tablebox" style="margin-top:16px"><h2 style="font-size:17px">By kitchen</h2>
  <table><thead><tr><th>Kitchen</th><th>Income</th><th>Expenses</th><th>Profit</th><th>Orders</th></tr></thead><tbody>
  @foreach($byKitchen as $k)<tr><td><b>{{ $k['name'] }}</b></td><td>₹{{ number_format($k['income']/100) }}</td><td>₹{{ number_format($k['expense']/100) }}</td><td>₹{{ number_format(($k['income']-$k['expense'])/100) }}</td><td>{{ $k['orders'] }}</td></tr>@endforeach
  </tbody></table></section>
@endsection
