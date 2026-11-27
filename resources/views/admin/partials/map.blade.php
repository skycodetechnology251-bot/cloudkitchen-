@push('head')<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">@endpush
<div id="zone-map" style="height:380px;border-radius:16px;border:1px solid var(--line)"></div>
<div class="row" style="margin-top:8px"><button type="button" class="btn ghost small" id="map-undo">Undo last point</button><button type="button" class="btn ghost small" id="map-clear">Clear</button><span class="small muted" id="map-count"></span></div>
<textarea name="{{ $name }}" id="f_{{ $name }}" class="sr" aria-label="Boundary points">{{ is_array($val) ? json_encode($val) : ($val ?: '[]') }}</textarea>
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
  var field = document.getElementById('f_{{ $name }}');
  var pts = []; try { pts = JSON.parse(field.value) || []; } catch(e) {}
  var map = L.map('zone-map').setView(pts.length ? pts[0] : [13.0067, 80.2570], 13);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
  var poly = L.polygon(pts, { color: '#0F5C4D', weight: 3, fillOpacity: .2 }).addTo(map);
  function sync(){ poly.setLatLngs(pts); field.value = JSON.stringify(pts.map(function(p){ return [+p[0].toFixed(6), +p[1].toFixed(6)]; })); document.getElementById('map-count').textContent = pts.length + ' points · click the map to add'; }
  map.on('click', function(e){ pts.push([e.latlng.lat, e.latlng.lng]); sync(); });
  document.getElementById('map-undo').onclick = function(){ pts.pop(); sync(); };
  document.getElementById('map-clear').onclick = function(){ pts = []; sync(); };
  if (pts.length > 2) map.fitBounds(poly.getBounds());
  sync();
})();
</script>
@endpush
