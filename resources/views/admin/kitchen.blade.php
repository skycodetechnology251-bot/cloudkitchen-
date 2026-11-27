@extends('layouts.admin')
@section('title', 'Kitchen · '.$kitchen->name)
@section('content')
<div class="row between" style="margin-bottom:16px"><div><span class="chip">KITCHEN · {{ strtoupper($kitchen->name) }}</span><h1 style="font-size:28px;margin-top:6px">Lunch production</h1></div>
  <div class="row"><label for="kd" class="sr">Date</label><input type="date" id="kd" value="{{ now('Asia/Kolkata')->toDateString() }}"><span class="small muted" id="kupd">Updating…</span></div></div>
<div class="grid g4" id="kcounts" style="margin-bottom:16px"></div>
<section class="card" id="kbatches" style="margin-bottom:16px;display:none"><h2 style="font-size:17px">Waiting for pickup · tell the rider this code at the counter</h2><div class="row" id="kbatchlist" style="gap:12px;margin-top:10px"></div></section>
<div class="grid g2">
  <section class="card tablebox"><h2 style="font-size:17px">By dish</h2><table><thead><tr><th>Dish</th><th>Sold</th><th>Packed</th><th>Portions left</th><th></th></tr></thead><tbody id="kdishes"></tbody></table></section>
  <section class="card"><div class="row between"><h2 style="font-size:17px">Orders</h2>
    <div class="row" style="flex-wrap:nowrap"><label for="krider" class="sr">Rider</label><select id="krider" style="min-width:160px"><option value="">Choose rider</option>@foreach($riders as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select><button class="btn small" id="kdispatch">Send packed orders</button></div></div>
    <div id="korders"></div></section>
</div>
@push('scripts')
<script>
(function(){
  var API = '/api/v1', TOKEN = @json($token), KID = {{ $kitchen->id }};
  var H = { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' };
  var esc = function(s){ return String(s ?? '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
  var next = { confirmed: ['accepted','Accept'], accepted: ['preparing','Start cooking'], preparing: ['packed','Mark packed'] };
  var data = null;
  function load(){
    fetch(API + '/kitchen/' + KID + '/board?date=' + document.getElementById('kd').value, { headers: H }).then(function(r){ return r.json(); }).then(function(d){ data = d; render(); document.getElementById('kupd').textContent = 'Updated ' + new Date().toLocaleTimeString(); });
  }
  function render(){
    var c = data.counts || {};
    document.getElementById('kcounts').innerHTML = [['Confirmed', (c.confirmed||0)], ['Cooking', (c.accepted||0)+(c.preparing||0)], ['Packed', c.packed||0], ['Out / delivered', (c.picked_up||0)+(c.delivered||0)]]
      .map(function(k){ return '<div class="card lift"><div class="small muted" style="font-weight:700">'+k[0]+'</div><div style="font-size:30px;font-weight:800">'+k[1]+'</div></div>'; }).join('');
    var bs = data.batches || [];
    document.getElementById('kbatches').style.display = bs.length ? '' : 'none';
    document.getElementById('kbatchlist').innerHTML = bs.map(function(b){ return '<div class="card lift" style="min-width:180px"><div class="small muted" style="font-weight:700">'+esc(b.code)+' · '+esc(b.rider||'')+' · '+b.packages+' pkg</div><div style="font-size:34px;font-weight:800;letter-spacing:6px">'+esc(b.pickup_code)+'</div><span class="chip grey">'+(b.status==='offered'?'Waiting for rider to accept':'Rider accepted')+'</span></div>'; }).join('');
    document.getElementById('kdishes').innerHTML = data.dishes.map(function(d){
      var left = d.capacity - d.sold - d.reserved;
      return '<tr><td><b>'+esc(d.dish)+'</b></td><td>'+d.sold+'</td><td><div style="height:8px;border-radius:99px;background:#EEF3F1;width:120px"><div class="grow" style="height:8px;border-radius:99px;background:var(--teal);width:'+(d.sold?Math.round(d.packed/d.sold*100):0)+'%"></div></div><span class="small">'+d.packed+'</span></td><td>'+left+'</td><td><button class="btn ghost small" data-menu="'+d.daily_menu_id+'" data-status="'+(d.status==='open'?'paused':'open')+'">'+(d.status==='open'?'Pause':'Resume')+'</button></td></tr>';
    }).join('');
    document.getElementById('korders').innerHTML = data.orders.map(function(o){
      var n = next[o.status];
      return '<div class="row between" style="padding:10px 0;border-bottom:1px solid var(--line);'+(o.allergy?'border-left:4px solid #C4562A;padding-left:10px':'')+'">'
        + '<div>'+(o.status==='packed'&&o.type==='delivery'?'<input type="checkbox" class="kpick" value="'+o.id+'" aria-label="Select '+esc(o.number)+'"> ':'')+'<b>'+esc(o.number)+'</b> <span class="chip grey">'+esc(o.status)+'</span>'+(o.allergy?' <span class="chip bad">Allergy · separate bag</span>':'')
        + '<div class="small muted">'+o.items.map(function(i){ return i.quantity+' × '+esc(i.name); }).join(', ')+' · '+esc(o.slot||o.type)+'</div></div>'
        + (n ? '<button class="btn small" data-order="'+o.id+'" data-to="'+n[0]+'" data-expected="'+o.status+'">'+n[1]+'</button>' : '') + '</div>';
    }).join('') || '<p class="muted">No orders yet.</p>';
  }
  document.addEventListener('click', function(e){
    var b = e.target.closest('button'); if (!b) return;
    if (b.dataset.order) fetch(API + '/kitchen/orders/' + b.dataset.order + '/transition', { method: 'POST', headers: H, body: JSON.stringify({ to: b.dataset.to, expected: b.dataset.expected }) }).then(load);
    if (b.dataset.menu) fetch(API + '/kitchen/menus/' + b.dataset.menu, { method: 'PATCH', headers: H, body: JSON.stringify({ status: b.dataset.status }) }).then(load);
  });
  document.getElementById('kdispatch').onclick = function(){
    var ids = Array.from(document.querySelectorAll('.kpick:checked')).map(function(i){ return +i.value; }), rider = document.getElementById('krider').value;
    if (!ids.length || !rider) { alert('Tick packed orders and choose a rider.'); return; }
    fetch(API + '/kitchen/' + KID + '/dispatch', { method: 'POST', headers: H, body: JSON.stringify({ order_ids: ids, rider_id: +rider }) }).then(function(r){ return r.json(); }).then(function(b){ if (b.code) load(); });
  };
  document.getElementById('kd').onchange = load;
  load(); setInterval(load, 20000);
})();
</script>
@endpush
@endsection
