<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Receipt {{ $order->number }}</title>
<style>@page{size:80mm auto;margin:3mm}body{font:12px/1.5 'Courier New',monospace;color:#000;width:72mm;margin:0 auto}.c{text-align:center}.r{display:flex;justify-content:space-between}hr{border:0;border-top:1px dashed #000}@media print{.noprint{display:none}}</style></head>
<body onload="window.print()">
<div class="c"><b style="font-size:16px">DAILY TABLE</b><br>{{ $order->kitchen->name }}<br>{{ $order->kitchen->address }}<br>GSTIN {{ $order->kitchen->gstin ?? '—' }}<br>FSSAI {{ $order->kitchen->fssai ?? '—' }}</div><hr>
<div class="c"><b>TAX INVOICE</b></div>
<div class="r"><span>Bill</span><span>{{ $order->invoice?->number }}</span></div>
<div class="r"><span>Date</span><span>{{ $order->created_at->timezone('Asia/Kolkata')->format('d-m-Y H:i') }}</span></div>
<div class="r"><span>Order</span><span>{{ $order->number }}</span></div>
<div class="r"><span>Type</span><span>{{ str_replace('_', ' ', $order->order_type) }}</span></div>
<div class="r"><span>Cashier</span><span>{{ $order->cashier?->name }}</span></div><hr>
@foreach($order->items as $i)<div>{{ $i->name }}</div><div class="r"><span>&nbsp;&nbsp;{{ $i->quantity }} x {{ number_format($i->unit_price_paise/100, 2) }}</span><span>{{ number_format($i->line_total_paise/100, 2) }}</span></div>@endforeach<hr>
@if($order->discount_paise)<div class="r"><span>Discount</span><span>-{{ number_format($order->discount_paise/100, 2) }}</span></div>@endif
<div class="r"><span>Taxable</span><span>{{ number_format($order->taxable_paise/100, 2) }}</span></div>
<div class="r"><span>CGST {{ config('dailytable.gst.rate_percent')/2 }}%</span><span>{{ number_format($order->cgst_paise/100, 2) }}</span></div>
<div class="r"><span>SGST {{ config('dailytable.gst.rate_percent')/2 }}%</span><span>{{ number_format($order->sgst_paise/100, 2) }}</span></div>
<div class="r"><span>Round off</span><span>{{ number_format($order->round_off_paise/100, 2) }}</span></div><hr>
<div class="r" style="font-size:15px"><b>TOTAL</b><b>Rs {{ number_format($order->total_paise/100, 2) }}</b></div>
@foreach($order->payments as $p)<div class="r"><span>Paid {{ strtoupper(str_replace('_pos', '', $p->method)) }}</span><span>{{ number_format($p->amount_paise/100, 2) }}</span></div>@endforeach<hr>
<div class="c">Invoice also in the Daily Table app.<br><b>Thank you. Eat well!</b></div>
<button class="noprint" onclick="window.print()">Print</button>
</body></html>
