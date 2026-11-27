@php($d = is_array($val) ? $val : (json_decode($val ?? '', true) ?: ['layout' => 'split', 'bg' => '#0F5C4D', 'ink' => '#FFFFFF', 'accent' => '#BFE8D6', 'show_code' => true, 'show_points' => false]))
<div class="grid g2" style="align-items:start">
  <div>
    <div class="field"><label for="bd-layout">Layout</label><select id="bd-layout"><option value="split">Image right</option><option value="full">Big headline</option><option value="coupon">Coupon card</option></select></div>
    <div class="field"><label>Colour</label><div class="row" id="bd-swatches">
      @foreach([['#0F5C4D','#FFFFFF','#BFE8D6'],['#EDE6FA','#2A1E52','#6B3FA0'],['#FBE3CF','#3E220C','#9A4512'],['#12302A','#FFFFFF','#F2CF4A'],['#FFF4C7','#3E2E05','#8A6A00'],['#E6F1FB','#13324F','#24557F']] as $s)
        <button type="button" class="lift" data-c='@json($s)' aria-label="Colour {{ $s[0] }}" style="width:40px;height:40px;border-radius:12px;border:1px solid #D5E2DD;background:{{ $s[0] }};cursor:pointer"></button>
      @endforeach
    </div></div>
    <label class="row" style="font-weight:600"><input type="checkbox" id="bd-code"> Show offer code</label>
    <label class="row" style="font-weight:600"><input type="checkbox" id="bd-points"> Show reward points badge</label>
  </div>
  <div>
    <div class="small muted" style="font-weight:800;margin-bottom:8px">LIVE PREVIEW</div>
    <div id="bd-preview" style="position:relative;height:180px;border-radius:24px;padding:18px;overflow:hidden;display:flex;flex-direction:column;gap:6px;align-items:flex-start;transition:background-color .3s"></div>
  </div>
</div>
<textarea name="{{ $name }}" id="f_{{ $name }}" class="sr" aria-label="Banner design">{{ json_encode($d) }}</textarea>
@push('scripts')
<script>
(function(){
  var field = document.getElementById('f_{{ $name }}'), d = JSON.parse(field.value), pv = document.getElementById('bd-preview');
  var esc = function(s){ return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); };
  var val = function(n){ var el = document.querySelector('[name="'+n+'"]'); return el ? esc(el.value) : ''; };
  function render(){
    field.value = JSON.stringify(d);
    pv.style.background = d.bg; pv.style.color = d.ink; pv.style.border = d.layout === 'coupon' ? '2px dashed ' + d.accent : '0';
    pv.style.justifyContent = d.layout === 'full' ? 'flex-end' : 'flex-start';
    pv.innerHTML = (d.show_points ? '<span style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:800;background:rgba(255,255,255,.85);color:#12302A;padding:4px 8px;border-radius:99px">+50 points</span>' : '')
      + '<span style="font-size:11px;font-weight:800;letter-spacing:.8px;color:'+d.accent+'">'+(val('tag')||'TAG')+'</span>'
      + '<b style="font-size:'+(d.layout==='full'?28:22)+'px;line-height:1.05;max-width:70%">'+(val('title')||'Headline')+'</b>'
      + '<span style="font-size:13px;opacity:.85">'+(val('subtitle')||'')+'</span>'
      + (d.show_code ? '<span style="font-size:11px;font-weight:800;border:1.5px dashed '+d.accent+';padding:3px 8px;border-radius:8px">CODE</span>' : '')
      + '<span style="margin-top:auto;background:'+d.accent+';color:'+(d.bg==='#0F5C4D'||d.bg==='#12302A'?'#12302A':'#fff')+';font-size:12px;font-weight:800;padding:6px 12px;border-radius:99px">'+(val('cta')||'Order now')+' →</span>';
  }
  var layout = document.getElementById('bd-layout'); layout.value = d.layout; layout.onchange = function(){ d.layout = layout.value; render(); };
  document.querySelectorAll('#bd-swatches button').forEach(function(b){ b.onclick = function(){ var c = JSON.parse(b.dataset.c); d.bg = c[0]; d.ink = c[1]; d.accent = c[2]; render(); }; });
  var code = document.getElementById('bd-code'), pts = document.getElementById('bd-points');
  code.checked = !!d.show_code; pts.checked = !!d.show_points;
  code.onchange = function(){ d.show_code = code.checked; render(); }; pts.onchange = function(){ d.show_points = pts.checked; render(); };
  ['tag','title','subtitle','cta'].forEach(function(n){ var el = document.querySelector('[name="'+n+'"]'); if (el) el.addEventListener('input', render); });
  render();
})();
</script>
@endpush
