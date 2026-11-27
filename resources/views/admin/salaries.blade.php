@extends('layouts.admin')
@section('title', 'Salaries & payroll')
@section('content')
@php
  $inr = fn ($p) => '₹'.number_format($p / 100, 2);
  $inr0 = fn ($p) => '₹'.number_format($p / 100);
  $roleTone = ['admin' => 'purple', 'ops' => 'blue', 'kitchen_manager' => 'coral', 'kitchen_staff' => 'amber', 'cashier' => 'teal', 'support' => 'sky', 'finance' => 'green', 'dietitian' => 'pink', 'doctor' => 'purple', 'rider' => 'blue'];
  $typeTone = ['monthly' => 'teal', 'daily' => 'amber', 'hourly' => 'sky', 'per_delivery' => 'blue'];
  $paidCount = $rows->whereNotNull('payment')->count();
  $due = $rows->whereNull('payment')->sum('suggested');
  $m = $month->format('Y-m');
  $prev = $month->copy()->subMonth()->format('Y-m');
  $next = $month->copy()->addMonth()->format('Y-m');
@endphp

<div class="pagehead" style="background:linear-gradient(120deg,#167D49 0%,#0F5C4D 45%,#2554B8 100%)">
  <div class="row between" style="align-items:flex-end;gap:16px;position:relative;z-index:1">
    <div>
      <h1>Salaries &amp; payroll</h1>
      <p>Set how each role is paid, give anyone their own amount in Staff, then pay everyone each month. Every payment is saved as a salary expense in Reports, with a payslip.</p>
    </div>
    <form method="get" class="row" style="gap:6px;background:rgba(255,255,255,.16);padding:6px;border-radius:999px;flex-wrap:nowrap">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <a class="btn small" style="background:rgba(255,255,255,.2)" href="?tab={{ $tab }}&month={{ $prev }}" aria-label="Previous month">‹</a>
      <label for="month" class="sr">Month</label>
      <input id="month" type="month" name="month" value="{{ $m }}" onchange="this.form.submit()" style="min-height:36px;border-radius:999px;border:0;padding:0 12px;font:800 14px Manrope,sans-serif">
      <a class="btn small" style="background:rgba(255,255,255,.2)" href="?tab={{ $tab }}&month={{ $next }}" aria-label="Next month">›</a>
    </form>
  </div>
</div>

<div class="row between" style="margin-bottom:16px;gap:12px">
  <nav class="seg" aria-label="Sections">
    <a class="{{ $tab === 'payroll' ? 'on' : '' }}" href="?tab=payroll&month={{ $m }}" @if($tab === 'payroll') aria-current="page" @endif>Payroll · {{ $month->format('F Y') }}</a>
    <a class="{{ $tab === 'roles' ? 'on' : '' }}" href="?tab=roles&month={{ $m }}" @if($tab === 'roles') aria-current="page" @endif>Salary by role</a>
  </nav>
  @if($tab === 'payroll')
    <div class="row" style="gap:8px;flex-wrap:nowrap">
      <label for="pay-search" class="sr">Search staff</label>
      <input id="pay-search" type="search" placeholder="Search name or role" style="min-height:40px;border-radius:999px;border:1px solid var(--line);padding:0 14px;background:#fff">
      <label class="chip amber" style="cursor:pointer;min-height:40px;padding:0 14px;white-space:nowrap"><input type="checkbox" id="pay-due" style="margin:0 6px 0 0"> Only unpaid</label>
    </div>
  @endif
</div>

@if($tab === 'roles')
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr))">
    @foreach($roles as $role)
      @php $tone = $roleTone[$role->slug] ?? 'teal'; @endphp
      <form method="post" action="{{ route('admin.salaries.role', $role) }}" class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column">@csrf @method('PUT')
        <input type="hidden" name="month" value="{{ $m }}">
        <div class="t-{{ $tone }}" style="padding:16px 18px;color:#fff">
          <div class="row between"><b style="font-size:18px">{{ $role->name }}</b><span class="chip" style="background:rgba(255,255,255,.22);color:#fff">{{ $role->users_count }} active</span></div>
          <div style="font-size:22px;font-weight:800;margin-top:8px">{{ \App\Models\Role::salaryText($role->salary_type, $role->salary_paise, $role->per_delivery_paise) }}</div>
        </div>
        <div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div class="field" style="margin:0"><label for="t{{ $role->id }}">Paid</label>
            <select id="t{{ $role->id }}" name="salary_type">@foreach($types as $k => $l)<option value="{{ $k }}" @selected($role->salary_type === $k)>{{ $l }}</option>@endforeach</select></div>
          <div class="field" style="margin:0"><label for="s{{ $role->id }}">Amount (₹)</label>
            <input id="s{{ $role->id }}" type="number" step="0.01" min="0" name="salary" value="{{ $role->salary_paise ? number_format($role->salary_paise / 100, 2, '.', '') : '' }}" placeholder="e.g. 18000"></div>
          <div class="field" style="margin:0;grid-column:1/-1"><label for="d{{ $role->id }}">Extra per delivery (₹) <span class="muted small">riders</span></label>
            <input id="d{{ $role->id }}" type="number" step="0.01" min="0" name="per_delivery" value="{{ $role->per_delivery_paise ? number_format($role->per_delivery_paise / 100, 2, '.', '') : '' }}" placeholder="0"></div>
          <button class="btn small" style="grid-column:1/-1">Save {{ $role->name }} salary</button>
        </div>
      </form>
    @endforeach
  </div>
  <p class="small muted" style="margin-top:12px">Staff with their own amount (Staff → edit → Salary) keep it when you change the role's salary.</p>
@else
  <div class="grid g4" style="margin-bottom:14px">
    <div class="kpi t-blue"><div class="kpi-label">Staff on payroll</div><div class="kpi-value">{{ $rows->count() }}</div><div class="kpi-sub">{{ $month->format('F Y') }}</div></div>
    <div class="kpi t-green"><div class="kpi-label">Paid</div><div class="kpi-value">{{ $paidCount }} <span style="font-size:16px;opacity:.85">of {{ $rows->count() }}</span></div><div class="kpi-sub">{{ $rows->count() - $paidCount }} still to pay</div></div>
    <div class="kpi t-purple"><div class="kpi-label">Total paid</div><div class="kpi-value">{{ $inr0($totalPaid) }}</div><div class="kpi-sub">Saved as salary expenses</div></div>
    <div class="kpi t-amber"><div class="kpi-label">Still to pay (estimate)</div><div class="kpi-value">{{ $inr0($due) }}</div><div class="kpi-sub">Before bonus and deductions</div></div>
  </div>
  <div class="card" style="margin-bottom:16px;padding:14px 18px">
    <div class="row between small" style="font-weight:800;margin-bottom:8px"><span>Payroll progress</span><span>{{ $rows->count() ? round($paidCount / $rows->count() * 100) : 0 }}% paid</span></div>
    <div class="bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $rows->count() }}" aria-valuenow="{{ $paidCount }}"><span style="width:{{ $rows->count() ? $paidCount / $rows->count() * 100 : 0 }}%"></span></div>
  </div>

  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(min(420px,100%),1fr))" id="pay-list">
    @forelse($rows as $row)
      @php
        $u = $row['user']; $p = $row['plan']; $pay = $row['payment'];
        $tone = $roleTone[$u->role->slug] ?? 'teal';
        $initials = collect(explode(' ', $u->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('');
      @endphp
      <article class="card pay-card" data-name="{{ strtolower($u->name.' '.$u->role->name) }}" data-paid="{{ $pay ? 1 : 0 }}" style="border-top:4px solid var(--c-{{ $pay ? 'green' : 'amber' }});display:flex;flex-direction:column;gap:12px">
        <div class="row between" style="flex-wrap:nowrap;gap:12px">
          <div class="row" style="flex-wrap:nowrap;gap:12px">
            <span class="avatar i-{{ $tone }}" aria-hidden="true">{{ strtoupper($initials) }}</span>
            <div><b style="font-size:16px">{{ $u->name }}</b>
              <div class="row" style="gap:6px;margin-top:4px"><span class="chip {{ $tone }}">{{ $u->role->name }}</span>@if($u->joined_on)<span class="small muted">since {{ $u->joined_on->format('M Y') }}</span>@endif</div></div>
          </div>
          @if($pay)<span class="chip green" style="font-size:13px">✓ Paid</span>@else<span class="chip amber" style="font-size:13px">Due</span>@endif
        </div>

        <div class="row" style="gap:8px">
          <span class="chip {{ $typeTone[$p['type']] ?? 'grey' }}">{{ \App\Models\Role::salaryText($p['type'], $p['paise'], $p['per_delivery_paise']) }}</span>
          <span class="chip grey">{{ $p['own'] ? 'Own amount' : 'From role' }}</span>
          @if($p['type'] === 'per_delivery' || $p['per_delivery_paise'])<span class="chip sky">{{ $row['deliveries'] }} deliveries</span>@endif
        </div>

        @if($pay)
          <div class="soft s-green row between" style="gap:10px">
            <div>
              <div style="font-size:26px;font-weight:800">{{ $inr($pay->net_paise) }}</div>
              <div class="small muted">{{ strtoupper($pay->method) }}{{ $pay->reference ? ' · '.$pay->reference : '' }} · {{ $pay->paid_at->timezone(config('dailytable.timezone'))->format('d M Y') }}@if($pay->bonus_paise) · bonus {{ $inr0($pay->bonus_paise) }}@endif @if($pay->deduction_paise) · deducted {{ $inr0($pay->deduction_paise) }}@endif</div>
            </div>
            <div class="row" style="gap:6px;flex-wrap:nowrap">
              <a class="btn small" href="{{ route('admin.salaries.payslip', $pay) }}" target="_blank" rel="noopener">Payslip</a>
              <form method="post" action="{{ route('admin.salaries.destroy', $pay) }}" onsubmit="return confirm('Remove this payment and its expense entry?')">@csrf @method('DELETE')<button class="btn ghost small" aria-label="Undo payment for {{ $u->name }}">Undo</button></form>
            </div>
          </div>
        @else
          <form method="post" action="{{ route('admin.salaries.pay', $u) }}" class="payrow soft s-amber" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:8px;align-items:end"
                data-base="{{ $p['type'] === 'monthly' ? $p['paise'] : ($p['type'] === 'per_delivery' ? $p['paise'] * $row['deliveries'] : 0) }}"
                data-rate="{{ in_array($p['type'], ['daily','hourly']) ? $p['paise'] : 0 }}"
                data-extra="{{ $p['type'] === 'per_delivery' ? 0 : $row['deliveries'] * $p['per_delivery_paise'] }}">@csrf
            <input type="hidden" name="month" value="{{ $m }}">
            @if(in_array($p['type'], ['daily','hourly']))
              <label class="small" style="font-weight:800">{{ $p['type'] === 'daily' ? 'Days worked' : 'Hours worked' }}<input name="units" type="number" step="0.5" min="0" max="744" required placeholder="0" style="width:100%;min-height:40px;border-radius:10px;border:1px solid var(--line);padding:0 10px;margin-top:4px"></label>
            @endif
            <label class="small" style="font-weight:800">Bonus ₹<input name="bonus" type="number" step="0.01" min="0" placeholder="0" style="width:100%;min-height:40px;border-radius:10px;border:1px solid var(--line);padding:0 10px;margin-top:4px"></label>
            <label class="small" style="font-weight:800">Deduct ₹<input name="deduction" type="number" step="0.01" min="0" placeholder="0" style="width:100%;min-height:40px;border-radius:10px;border:1px solid var(--line);padding:0 10px;margin-top:4px"></label>
            <label class="small" style="font-weight:800">Paid by<select name="method" style="width:100%;min-height:40px;border-radius:10px;border:1px solid var(--line);padding:0 8px;margin-top:4px;background:#fff"><option value="bank">Bank</option><option value="upi">UPI</option><option value="cash">Cash</option></select></label>
            <label class="small" style="font-weight:800">Ref / UTR<input name="reference" maxlength="80" placeholder="optional" style="width:100%;min-height:40px;border-radius:10px;border:1px solid var(--line);padding:0 10px;margin-top:4px"></label>
            <button class="btn" style="grid-column:1/-1;background:linear-gradient(135deg,#34B57A,#167D49)"><span class="net">Pay {{ $inr($row['suggested']) }}</span></button>
            @error('pay_'.$u->id)<div class="small" style="color:var(--bad-ink);grid-column:1/-1;font-weight:700">{{ $message }}</div>@enderror
          </form>
        @endif
      </article>
    @empty
      <div class="card muted">No staff yet. Add people in Staff.</div>
    @endforelse
  </div>
  <p class="small muted" style="margin-top:12px">Monthly staff get the full amount; use Deduct for unpaid leave. Daily and hourly staff: enter days or hours. Riders' delivery pay counts deliveries completed in the month.</p>
@endif
@endsection
@push('scripts')
<script>
  // Live "Pay ₹…" total as bonus / deduction / days are typed.
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
  // Search and "only unpaid" filter.
  (function () {
    var q = document.getElementById('pay-search'), due = document.getElementById('pay-due');
    if (!q) return;
    function filter() {
      var t = q.value.trim().toLowerCase();
      document.querySelectorAll('.pay-card').forEach(function (c) {
        c.hidden = (t && c.dataset.name.indexOf(t) < 0) || (due.checked && c.dataset.paid === '1');
      });
    }
    q.addEventListener('input', filter); due.addEventListener('change', filter);
  })();
</script>
@endpush
