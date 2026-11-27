@extends('layouts.admin')
@section('title', 'POS · '.$kitchen->name)
@section('content')
<div class="row between" style="margin-bottom:14px;align-items:flex-end"><div><span class="chip">POS · {{ strtoupper($kitchen->name) }}</span><h1 style="font-size:26px;margin-top:6px">Counter</h1></div><span class="chip grey">Billing as {{ auth()->user()->name }}</span></div>
@if($kitchens->count() > 1)
  <nav class="row" aria-label="Kitchen" style="margin-bottom:14px;gap:8px">
    @foreach($kitchens as $k)
      <a href="{{ route('admin.pos', $k) }}" class="btn small {{ $k->id === $kitchen->id ? 'dark' : 'ghost' }}" @if($k->id === $kitchen->id) aria-current="page" @endif>{{ $k->name }}</a>
    @endforeach
  </nav>
@endif
<div class="row" style="align-items:flex-start">
  <section style="flex:999 1 520px;min-width:0">
    <div class="row" id="pcats" style="margin-bottom:12px"></div>
    <div id="pgrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px"></div>
  </section>
  <section class="card" style="flex:1 1 340px;max-width:420px;position:sticky;top:20px">
    <h2 style="font-size:18px">Current order</h2>
    <div class="row" style="margin:10px 0" id="ptypes"></div>
    <div class="field"><label for="pphone">Customer mobile (optional)</label><input id="pphone" inputmode="numeric" maxlength="10" placeholder="For wallet, points and WhatsApp invoice"></div>
    <div id="plines" class="small"></div>
    <div class="field"><label for="pdisc">Discount %</label><input id="pdisc" type="number" min="0" max="50" value="0"></div>
    <div id="ptotals" style="margin:10px 0"></div>
    <div class="row" id="ppays"></div>
    <button class="btn" id="pcharge" style="width:100%;min-height:54px;margin-top:12px">Charge</button>
    <div id="pdone"></div>
  </section>
</div>
@push('scripts')
<script>
(function(){
  var API = '/api/v1', TOKEN = @json($token), KID = {{ $kitchen->id }};
  var H = { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' };
  var esc = function(s){ return String(s ?? '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
  var menu = [], cart = {}, cat = 'All', type = 'takeaway', pay = 'cash', quote = null;
  var rs = function(p){ return '₹' + (p/100).toFixed(2); };
  function body(){ return { items: Object.keys(cart).map(function(id){ return { daily_menu_id: +id, quantity: cart[id] }; }), order_type: type, pay: pay, customer_phone: document.getElementById('pphone').value || null, discount_percent: +document.getElementById('pdisc').value || 0 }; }
  function draw(){
    var cats = ['All'].concat(Array.from(new Set(menu.map(function(m){ return m.category; }))));
    document.getElementById('pcats').innerHTML = cats.map(function(c){ return '<button class="btn small '+(c===cat?'dark':'ghost')+'" data-cat="'+esc(c)+'">'+esc(c)+'</button>'; }).join('');
    document.getElementById('pgrid').innerHTML = menu.filter(function(m){ return cat==='All'||m.category===cat; }).map(function(m){
      return '<button class="card lift" data-add="'+m.daily_menu_id+'" '+(m.available<1?'disabled':'')+' style="text-align:left;cursor:pointer;'+(cart[m.daily_menu_id]?'border:2px solid var(--teal)':'')+'"><b>'+esc(m.name)+'</b><div class="small muted">'+esc(m.diet)+' · '+m.available+' left</div><div style="font-weight:800;margin-top:6px">'+rs(m.price_paise)+(cart[m.daily_menu_id]?' · ×'+cart[m.daily_menu_id]:'')+'</div></button>';
    }).join('');
    document.getElementById('ptypes').innerHTML = [['dine_in','Dine-in'],['takeaway','Takeaway'],['delivery','Phone delivery']].map(function(t){ return '<button class="btn small '+(t[0]===type?'dark':'ghost')+'" data-type="'+t[0]+'">'+t[1]+'</button>'; }).join('');
    document.getElementById('ppays').innerHTML = [['cash','Cash'],['upi_pos','UPI'],['card_pos','Card'],['wallet','Wallet']].map(function(p){ return '<button class="btn small '+(p[0]===pay?'':'ghost')+'" data-pay="'+p[0]+'">'+p[1]+'</button>'; }).join('');
    document.getElementById('plines').innerHTML = Object.keys(cart).map(function(id){ var m = menu.find(function(x){ return x.daily_menu_id==id; }); return '<div class="row between" style="padding:6px 0;border-bottom:1px solid var(--line)"><span>'+cart[id]+' × '+esc(m.name)+'</span><span class="row" style="flex-wrap:nowrap"><button class="btn ghost small" data-dec="'+id+'" aria-label="One less">−</button><button class="btn mint small" data-add="'+id+'" aria-label="One more">+</button></span></div>'; }).join('') || '<p class="muted">Tap dishes to add them.</p>';
    document.getElementById('ptotals').innerHTML = quote ? '<div class="row between"><span class="muted">Items</span><span>'+rs(quote.subtotal_paise)+'</span></div><div class="row between"><span class="muted">Discount</span><span>−'+rs(quote.discount_paise)+'</span></div><div class="row between"><span class="muted">CGST + SGST</span><span>'+rs(quote.cgst_paise+quote.sgst_paise)+'</span></div><div class="row between" style="font-size:22px;font-weight:800"><span>Total</span><span>'+rs(quote.total_paise)+'</span></div>' : '';
    document.getElementById('pcharge').textContent = quote ? 'Charge ' + rs(quote.total_paise) : 'Charge';
  }
  function requote(){ if (!Object.keys(cart).length) { quote = null; return draw(); } fetch(API+'/pos/'+KID+'/quote', { method:'POST', headers:H, body: JSON.stringify(body()) }).then(function(r){ return r.json(); }).then(function(q){ quote = q.total_paise !== undefined ? q : null; draw(); }); }
  document.addEventListener('click', function(e){
    var b = e.target.closest('button'); if (!b) return;
    if (b.dataset.cat) { cat = b.dataset.cat; draw(); }
    if (b.dataset.add) { cart[b.dataset.add] = (cart[b.dataset.add]||0)+1; requote(); }
    if (b.dataset.dec) { cart[b.dataset.dec]--; if (cart[b.dataset.dec] < 1) delete cart[b.dataset.dec]; requote(); }
    if (b.dataset.type) { type = b.dataset.type; requote(); }
    if (b.dataset.pay) { pay = b.dataset.pay; draw(); }
  });
  document.getElementById('pdisc').onchange = requote;
  document.getElementById('pcharge').onclick = function(){
    if (!quote) return;
    fetch(API+'/pos/'+KID+'/orders', { method:'POST', headers:H, body: JSON.stringify(body()) }).then(function(r){ return r.json().then(function(j){ return [r.ok, j]; }); }).then(function(res){
      if (!res[0]) { alert(res[1].message || 'Could not complete the sale.'); return; }
      document.getElementById('pdone').innerHTML = '<div class="flash rise" style="margin-top:12px">Paid · '+esc(res[1].order.invoice ? res[1].order.invoice.number : res[1].order.number)+' <a href="'+res[1].receipt_url+'" target="_blank">Print receipt</a></div>';
      cart = {}; quote = null; load();
    });
  };
  function load(){ fetch(API+'/pos/'+KID+'/menu', { headers:H }).then(function(r){ return r.json(); }).then(function(m){ menu = m; draw(); }); }
  load();
})();
</script>
@endpush
@endsection
