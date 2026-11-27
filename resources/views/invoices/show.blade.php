@php($s = $invoice->snapshot) @php($t = $s['totals']) @php($rate = $s['rate'] / 2)
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Invoice {{ $invoice->number }}</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="{{ asset('css/dt.css') }}">
<style>@page{size:A4;margin:14mm}body{background:#fff}.inv{max-width:794px;margin:0 auto;padding:40px}@media print{.noprint{display:none}.inv{padding:0}}th,td{font-size:12.5px;padding:9px 8px}</style></head>
<body><div class="inv">
  <div class="row between noprint" style="margin-bottom:16px"><span class="chip">Tax invoice</span><button class="btn small" onclick="window.print()">Print or save as PDF</button></div>
  <div class="row between" style="align-items:flex-start"><div class="row">@include('partials.logo')<div><b style="font-size:20px">Daily Table</b><br><span class="small muted">{{ $s['seller']['kitchen'] }}</span></div></div>
    <div style="text-align:right"><div style="font-size:26px;font-weight:800">TAX INVOICE</div><div class="small muted">Original for recipient</div></div></div>
  <div class="grid" style="grid-template-columns:repeat(3,1fr);margin:22px 0;font-size:12.5px;line-height:1.6">
    <div><div class="small muted" style="font-weight:800">SOLD BY</div><b>{{ $s['seller']['name'] }}</b><br>{{ $s['seller']['address'] }}<br>GSTIN {{ $s['seller']['gstin'] ?? '—' }}<br>FSSAI {{ $s['seller']['fssai'] ?? '—' }}</div>
    <div><div class="small muted" style="font-weight:800">BILLED TO</div><b>{{ $s['buyer']['name'] }}</b><br>@if($s['buyer']['phone']){{ substr($s['buyer']['phone'],0,2) }}••• ••{{ substr($s['buyer']['phone'],-3) }}<br>@endif @if($s['buyer']['address']){{ collect($s['buyer']['address'])->only(['flat','building','street','area','city','pincode'])->filter()->join(', ') }}<br>@endif Place of supply: {{ $s['place_of_supply'] }}</div>
    <div style="background:var(--bg);border-radius:14px;padding:12px 14px"><div class="row between"><span class="muted">Invoice no.</span><b>{{ $invoice->number }}</b></div><div class="row between"><span class="muted">Date</span><b>{{ $invoice->issued_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}</b></div><div class="row between"><span class="muted">Order</span><b>{{ $s['order']['number'] }}</b></div>@if($s['order']['cashier'])<div class="row between"><span class="muted">Cashier</span><b>{{ $s['order']['cashier'] }}</b></div>@endif</div>
  </div>
  <table><thead><tr><th>#</th><th>Item</th><th>SAC</th><th style="text-align:right">Qty</th><th style="text-align:right">Rate ₹</th><th style="text-align:right">Amount ₹</th></tr></thead><tbody>
  @foreach($s['lines'] as $i => $l)<tr><td>{{ $i + 1 }}</td><td><b>{{ $l['name'] }}</b></td><td>{{ $l['sac'] }}</td><td style="text-align:right">{{ $l['quantity'] }}</td><td style="text-align:right">{{ number_format($l['unit_price_paise']/100, 2) }}</td><td style="text-align:right">{{ number_format($l['line_total_paise']/100, 2) }}</td></tr>@endforeach
  </tbody></table>
  <div class="row between" style="align-items:flex-start;margin-top:20px">
    <div style="flex:1;font-size:12.5px;line-height:1.6"><div class="small muted" style="font-weight:800">AMOUNT IN WORDS</div><b>{{ \App\Services\InvoiceService::amountInWords(intdiv($t['total_paise'], 100)) }}</b>
      <div class="small muted" style="font-weight:800;margin-top:10px">PAYMENT</div>@foreach($s['payments'] as $p){{ strtoupper(str_replace('_', ' ', $p['method'])) }} ₹{{ number_format($p['amount_paise']/100, 2) }}{{ $p['gateway_payment_id'] ? ' · '.$p['gateway_payment_id'] : '' }}<br>@endforeach</div>
    <div style="width:280px;font-size:13px">
      <div class="row between"><span class="muted">Items</span><span>₹{{ number_format($t['subtotal_paise']/100, 2) }}</span></div>
      @if($t['discount_paise'])<div class="row between"><span class="muted">Discount</span><span>−₹{{ number_format($t['discount_paise']/100, 2) }}</span></div>@endif
      @if($t['delivery_paise'])<div class="row between"><span class="muted">Delivery</span><span>₹{{ number_format($t['delivery_paise']/100, 2) }}</span></div>@endif
      <div class="row between"><span class="muted">Taxable value</span><span>₹{{ number_format($t['taxable_paise']/100, 2) }}</span></div>
      <div class="row between"><span class="muted">CGST {{ $rate }}%</span><span>₹{{ number_format($t['cgst_paise']/100, 2) }}</span></div>
      <div class="row between"><span class="muted">SGST {{ $rate }}%</span><span>₹{{ number_format($t['sgst_paise']/100, 2) }}</span></div>
      <div class="row between"><span class="muted">Round off</span><span>{{ $t['round_off_paise'] < 0 ? '−' : '' }}₹{{ number_format(abs($t['round_off_paise'])/100, 2) }}</span></div>
      <div class="row between" style="font-size:20px;font-weight:800;padding:10px 14px;margin-top:6px;border-radius:12px;background:var(--teal);color:#fff"><span>Total</span><span>₹{{ number_format($t['total_paise']/100, 2) }}</span></div>
    </div>
  </div>
  <p class="small muted" style="border-top:1px solid var(--line);margin-top:28px;padding-top:14px">Restaurant service, GST at {{ $s['rate'] }}% without input tax credit. Refunds are issued as credit notes against this invoice number. This is a computer-generated invoice and needs no signature.</p>
</div></body></html>
