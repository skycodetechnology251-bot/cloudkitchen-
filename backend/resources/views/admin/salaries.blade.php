@extends('layouts.admin')
@section('title', 'Salaries & payroll')
@section('content')
@php($inr = fn ($p) => '₹'.number_format($p / 100, 2))
@php($unit = ['monthly' => '/ month', 'daily' => '/ day', 'hourly' => '/ hour', 'per_delivery' => '/ delivery'])
<div class="row between" style="margin-bottom:14px;align-items:flex-end">
  <div><h1 style="font-size:28px">Salaries &amp; payroll</h1><p class="muted small">Set a salary for each role, give anyone their own amount in Staff, then pay each month. Every payment is also recorded as a salary expense in Reports.</p></div>
  <form method="get" class="row" style="gap:8px">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label for="month" class="small muted" style="font-weight:700">Month</label>
    <input id="month" type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()" style="min-height:42px;border-radius:12px;border:1px solid var(--line);padding:0 10px">
  </form>
</div>

<nav class="row" style="gap:8px;margin-bottom:16px" aria-label="Sections">
  <a class="btn small {{ $tab === 'payroll' ? 'dark' : 'ghost' }}" href="?tab=payroll&month={{ $month->format('Y-m') }}" @if($tab === 'payroll') aria-current="page" @endif>Payroll · {{ $month->format('F Y') }}</a>
  <a class="btn small {{ $tab === 'roles' ? 'dark' : 'ghost' }}" href="?tab=roles&month={{ $month->format('Y-m') }}" @if($tab === 'roles') aria-current="page" @endif>Salary by role</a>
</nav>

@if($tab === 'roles')
  <div class="grid g2">
    @foreach($roles as $role)
      <form method="post" action="{{ route('admin.salaries.role', $role) }}" class="card lift" style="display:flex;flex-direction:column;gap:10px">@csrf @method('PUT')
        <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
        <div class="row between"><h2 style="font-size:18px">{{ $role->name }}</h2><span class="chip grey">{{ $role->users_count }} active</span></div>
        <p class="small muted" style="margin:0">{{ \App\Models\Role::salaryText($role->salary_type, $role->salary_paise, $role->per_delivery_paise) }}</p>
        <div class="grid g2" style="gap:10px">
          <div class="field" style="margin:0"><label for="t{{ $role->id }}">Paid</label>
            <select id="t{{ $role->id }}" name="salary_type">@foreach($types as $k => $l)<option value="{{ $k }}" @selected($role->salary_type === $k)>{{ $l }}</option>@endforeach</select></div>
          <div class="field" style="margin:0"><label for="s{{ $role->id }}">Amount (₹)</label>
            <input id="s{{ $role->id }}" type="number" step="0.01" min="0" name="salary" value="{{ $role->salary_paise ? number_format($role->salary_paise / 100, 2, '.', '') : '' }}" placeholder="e.g. 18000"></div>
          <div class="field" style="margin:0;grid-column:1/-1"><label for="d{{ $role->id }}">Extra per delivery (₹) <span class="muted small">— for riders; leave 0 for others</span></label>
            <input id="d{{ $role->id }}" type="number" step="0.01" min="0" name="per_delivery" value="{{ $role->per_delivery_paise ? number_format($role->per_delivery_paise / 100, 2, '.', '') : '' }}" placeholder="0"></div>
        </div>
        <button class="btn small" style="align-self:flex-start">Save {{ $role->name }} salary</button>
      </form>
    @endforeach
  </div>
  <p class="small muted" style="margin-top:12px">Staff with their own amount (Staff → edit → Salary) keep it when you change the role's salary.</p>
@else
  <div class="grid g4" style="margin-bottom:16px">
    <div class="card"><div class="small muted">Staff</div><div style="font-size:26px;font-weight:800">{{ $rows->count() }}</div></div>
    <div class="card"><div class="small muted">Paid this month</div><div style="font-size:26px;font-weight:800">{{ $rows->whereNotNull('payment')->count() }}</div></div>
    <div class="card"><div class="small muted">Total paid</div><div style="font-size:26px;font-weight:800">{{ $inr($totalPaid) }}</div></div>
    <div class="card"><div class="small muted">Still to pay (estimate)</div><div style="font-size:26px;font-weight:800">{{ $inr($rows->whereNull('payment')->sum('suggested')) }}</div></div>
  </div>

  <section class="card tablebox">
    <table>
      <thead><tr><th>Staff</th><th>Salary</th><th>This month</th><th style="min-width:420px">Pay</th></tr></thead>
      <tbody>
      @forelse($rows as $row)
        @php($u = $row['user'])
        @php($p = $row['plan'])
        @php($pay = $row['payment'])
        <tr>
          <td><b>{{ $u->name }}</b><div class="small muted">{{ $u->role->name }}@if($u->joined_on) · since {{ $u->joined_on->format('M Y') }}@endif</div></td>
          <td>{{ \App\Models\Role::salaryText($p['type'], $p['paise'], $p['per_delivery_paise']) }}<div class="small muted">{{ $p['own'] ? 'Own amount' : 'From role' }}</div></td>
          <td>
            @if($p['type'] === 'per_delivery' || $p['per_delivery_paise'])<div class="small">{{ $row['deliveries'] }} deliveries</div>@endif
            @if(! $pay)<div class="small muted">Suggested {{ $inr($row['suggested']) }}{{ in_array($p['type'], ['daily','hourly']) ? ' + days/hours' : '' }}</div>@endif
          </td>
          <td>
            @if($pay)
              <div class="row between" style="flex-wrap:nowrap;gap:10px">
                <div><span class="chip">Paid {{ $inr($pay->net_paise) }}</span><div class="small muted" style="margin-top:4px">{{ strtoupper($pay->method) }}{{ $pay->reference ? ' · '.$pay->reference : '' }} · {{ $pay->paid_at->timezone(config('dailytable.timezone'))->format('d M') }}</div></div>
                <div class="row" style="gap:6px;flex-wrap:nowrap">
                  <a class="btn ghost small" href="{{ route('admin.salaries.payslip', $pay) }}" target="_blank" rel="noopener">Payslip</a>
                  <form method="post" action="{{ route('admin.salaries.destroy', $pay) }}" onsubmit="return confirm('Remove this payment and its expense entry?')">@csrf @method('DELETE')<button class="btn ghost small" aria-label="Undo payment for {{ $u->name }}">Undo</button></form>
                </div>
              </div>
            @else
              <form method="post" action="{{ route('admin.salaries.pay', $u) }}" class="payrow" data-base="{{ $p['type'] === 'monthly' ? $p['paise'] : ($p['type'] === 'per_delivery' ? $p['paise'] * $row['deliveries'] : 0) }}" data-rate="{{ in_array($p['type'], ['daily','hourly']) ? $p['paise'] : 0 }}" data-extra="{{ $p['type'] === 'per_delivery' ? 0 : $row['deliveries'] * $p['per_delivery_paise'] }}" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center">@csrf
                <input type="hidden" name="month" value="{{ $month->format('Y-m') }}">
                @if(in_array($p['type'], ['daily','hourly']))
                  <input name="units" type="number" step="0.5" min="0" max="744" required placeholder="{{ $p['type'] === 'daily' ? 'Days' : 'Hours' }}" aria-label="{{ $p['type'] === 'daily' ? 'Days worked' : 'Hours worked' }}" value="{{ old('units') }}" style="width:76px">
                @endif
                <input name="bonus" type="number" step="0.01" min="0" placeholder="Bonus ₹" aria-label="Bonus for {{ $u->name }}" style="width:96px">
                <input name="deduction" type="number" step="0.01" min="0" placeholder="Deduct ₹" aria-label="Deduction for {{ $u->name }}" style="width:96px">
                <select name="method" aria-label="Paid by"><option value="bank">Bank</option><option value="upi">UPI</option><option value="cash">Cash</option></select>
                <input name="reference" maxlength="80" placeholder="Ref / UTR" aria-label="Payment reference" style="width:100px">
                <button class="btn small"><span class="net">Pay {{ $inr($row['suggested']) }}</span></button>
                @error('pay_'.$u->id)<div class="small" style="color:var(--bad-ink);flex-basis:100%">{{ $message }}</div>@enderror
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">No staff yet. Add people in Staff.</td></tr>
      @endforelse
      </tbody>
    </table>
  </section>
  <p class="small muted" style="margin-top:10px">Monthly staff get the full amount; use Deduct for unpaid leave. Daily and hourly staff: enter days or hours. Riders' delivery pay counts deliveries completed in the month.</p>
@endif
@endsection
@push('scripts')
<script>
  // Live "Pay ₹…" total on each row as bonus / deduction / days are typed.
  document.querySelectorAll('form.payrow').forEach(function (f) {
    var base = +f.dataset.base, rate = +f.dataset.rate, extra = +f.dataset.extra, out = f.querySelector('.net');
    function money(p) { return '₹' + (p / 100).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function calc() {
      var units = f.units ? +(f.units.value || 0) : 0;
      var net = base + Math.round(rate * units) + extra + Math.round((+f.bonus.value || 0) * 100) - Math.round((+f.deduction.value || 0) * 100);
      out.textContent = 'Pay ' + money(Math.max(0, net));
    }
    f.addEventListener('input', calc); calc();
  });
</script>
@endpush
