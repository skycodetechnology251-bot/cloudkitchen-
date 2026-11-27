<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payslip · {{ $p->user->name }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $p->month)->format('F Y') }}</title>
<style>
body{font-family:Manrope,system-ui,sans-serif;color:#12302A;margin:0;background:#F3F7F5}
.sheet{max-width:720px;margin:24px auto;background:#fff;border-radius:18px;padding:32px;box-shadow:0 20px 50px -30px rgba(18,48,42,.4)}
h1{margin:0;font-size:24px}table{width:100%;border-collapse:collapse;margin-top:18px}td{padding:10px 0;border-bottom:1px solid #E3ECE8}td:last-child{text-align:right;font-weight:700}
.tot td{font-size:18px;font-weight:800;border-bottom:0}.muted{color:#56706A;font-size:13px}.row{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap}
.btn{display:inline-block;margin:0 auto 24px;padding:10px 18px;border-radius:999px;background:#0F5C4D;color:#fff;font-weight:800;border:0;cursor:pointer}
@media print{body{background:#fff}.sheet{box-shadow:none;margin:0}.noprint{display:none}}
</style></head><body>
@php($inr = fn ($v) => '₹'.number_format($v / 100, 2))
@php($unit = ['daily' => 'days', 'hourly' => 'hours'][$p->salary_type] ?? null)
<div class="sheet">
  <div class="row"><div><h1>Payslip</h1><div class="muted">{{ config('app.name') }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $p->month)->format('F Y') }}</div></div>
    <div style="text-align:right"><b>{{ $p->user->name }}</b><div class="muted">{{ $p->role_name }}{{ $p->user->kitchens->first() ? ' · '.$p->user->kitchens->first()->name : '' }}</div>@if($p->user->joined_on)<div class="muted">Joined {{ $p->user->joined_on->format('d M Y') }}</div>@endif</div></div>
  <table>
    <tr><td>{{ \App\Models\Role::SALARY_TYPES[$p->salary_type] ?? 'Salary' }}@if($unit) · {{ rtrim(rtrim(number_format($p->units, 2), '0'), '.') }} {{ $unit }} × {{ $inr($p->rate_paise) }}@elseif($p->salary_type === 'per_delivery') · {{ $p->deliveries }} × {{ $inr($p->rate_paise) }}@endif</td><td>{{ $inr($p->base_paise) }}</td></tr>
    @if($p->delivery_pay_paise)<tr><td>Delivery pay · {{ $p->deliveries }} deliveries</td><td>{{ $inr($p->delivery_pay_paise) }}</td></tr>@endif
    @if($p->bonus_paise)<tr><td>Bonus</td><td>{{ $inr($p->bonus_paise) }}</td></tr>@endif
    @if($p->deduction_paise)<tr><td>Deductions</td><td>− {{ $inr($p->deduction_paise) }}</td></tr>@endif
    <tr class="tot"><td>Net pay</td><td>{{ $inr($p->net_paise) }}</td></tr>
  </table>
  <p class="muted">Paid by {{ ['bank' => 'bank transfer', 'upi' => 'UPI', 'cash' => 'cash'][$p->method] ?? $p->method }}{{ $p->reference ? ' · ref '.$p->reference : '' }} on {{ $p->paid_at->timezone(config('dailytable.timezone'))->format('d M Y') }}{{ $p->payer ? ' · recorded by '.$p->payer->name : '' }}.@if($p->note) {{ $p->note }}@endif</p>
</div>
<div style="text-align:center" class="noprint"><button class="btn" onclick="window.print()">Print or save as PDF</button></div>
</body></html>
