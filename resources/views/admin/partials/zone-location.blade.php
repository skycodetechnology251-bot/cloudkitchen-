{{--
  Zone location: Country → State → City dropdowns (each filtered by the one before), full address with
  search, "Use my current location", and the delivery boundary drawn on a map.
  $mode = 'point' (branches): no boundary, the pin is the place, saved as lat / lng; $overlays shows
  other zones faintly; $radiusInput (an input id, metres) draws a delivery-radius circle.
  Google Maps when Admin → Integrations → Google sign-in & Maps has a Maps key; OpenStreetMap otherwise.
--}}
@php
  $mapsKey = \App\Models\Setting::get('google', 'maps_api_key');
  $point = ($mode ?? 'zone') === 'point';
  $latName = $point ? 'lat' : 'center_lat';
  $lngName = $point ? 'lng' : 'center_lng';
  $v = fn ($k, $d = null) => old($k, data_get($item, $k) ?? $d);
  $init = [
    'polygon' => is_array($val) ? $val : (json_decode((string) $val, true) ?: []),
    'country' => $v('country', 'India'), 'country_code' => $v('country_code', 'IN'),
    'state' => $v('state', 'Tamil Nadu'), 'city' => $v('city', 'Chennai'),
    'center' => $v($latName) ? [(float) $v($latName), (float) $v($lngName)] : null,
    'overlays' => $overlays ?? [],
    'point' => $point,
    'radiusInput' => $radiusInput ?? null,
  ];
@endphp
@push('head')
<style>
  .zl-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
  .zl-grid label,.zl-label{display:block;font-size:13px;font-weight:800;margin-bottom:6px;color:#27403A}
  .zl-grid select,.zl-grid input,.zl-addr input{width:100%;min-height:44px;border-radius:12px;border:1px solid var(--line);padding:0 12px;background:#fff;font:inherit}
  .zl-addr{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.zl-addr input{flex:1 1 320px}
  .zl-map{height:440px;border-radius:18px;border:1px solid var(--line);margin-top:12px;overflow:hidden;background:#E9EFEC}
  .zl-tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-top:10px}
  .zl-tools select{min-height:36px;border-radius:999px;border:1px solid var(--line);padding:0 10px;background:#fff}
  .zl-status{font-size:13px;font-weight:700;margin-top:8px;min-height:18px}
  .zl-status.ok{color:var(--teal)}.zl-status.bad{color:var(--bad-ink)}
  .zl-vx{width:14px;height:14px;border-radius:50%;background:#fff;border:3px solid #0F5C4D;margin:-7px 0 0 -7px}
  .zl-err{color:var(--bad-ink);font-size:13px;font-weight:700;margin-top:4px}
</style>
@unless($mapsKey)<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">@endunless
@endpush

<div id="zl" data-locations="{{ asset('data/locations.json') }}">
  <div class="zl-grid">
    <div>
      <label for="zl-country">Country</label>
      <select id="zl-country" aria-label="Country"></select>
    </div>
    <div>
      <label for="zl-state">State</label>
      <select id="zl-state"></select>
      <input id="zl-state-text" type="text" placeholder="Type the state" hidden>
    </div>
    <div>
      <label for="zl-city">City</label>
      <select id="zl-city"></select>
      <input id="zl-city-text" type="text" placeholder="Type the city" hidden>
    </div>
    <div>
      <label for="zl-pincode">Pincode</label>
      <input id="zl-pincode" name="pincode" type="text" inputmode="numeric" maxlength="12" value="{{ $v('pincode') }}" placeholder="600020">
    </div>
  </div>
  @foreach(['country', 'state', 'city', 'pincode'] as $k) @error($k)<div class="zl-err">{{ $message }}</div>@enderror @endforeach

  <div class="zl-addr">
    <label for="zl-address" class="sr">Full address or landmark</label>
    <input id="zl-address" name="address" type="text" maxlength="255" value="{{ $v('address') }}" placeholder="Full address or landmark, e.g. Gandhi Nagar, Adyar">
    <button type="button" class="btn ghost small" id="zl-find">Find on map</button>
    <button type="button" class="btn small" id="zl-here">Use my current location</button>
  </div>
  <div class="zl-status" id="zl-status" role="status" aria-live="polite"></div>

  <div class="zl-map" id="zl-map" role="application" aria-label="{{ $point ? 'Map: click to place the pin' : 'Map: click to add boundary points' }}"></div>
  @if($point)
  <p class="small muted" style="margin:8px 0 0">Click the map or drag the pin to the exact spot. @if($overlays ?? [])Purple outlines are your delivery zones; the dashed green circle is how far this branch delivers.@endif {{ $mapsKey ? '' : 'Add a Google Maps key in Integrations to use Google Maps here.' }}</p>
  @foreach([$latName, $lngName] as $k) @error($k)<div class="zl-err">Place the pin on the map (search the address or use your current location).</div>@break @enderror @endforeach
  @else
  <div class="zl-tools">
    <span class="small muted" id="zl-count"></span>
    <button type="button" class="btn ghost small" id="zl-undo">Undo last point</button>
    <button type="button" class="btn ghost small" id="zl-clear">Clear boundary</button>
    <span class="small muted" style="margin-left:6px">Circle around the centre:</span>
    <select id="zl-radius" aria-label="Circle radius">
      <option value="1">1 km</option><option value="1.5">1.5 km</option><option value="2" selected>2 km</option><option value="3">3 km</option><option value="4">4 km</option><option value="5">5 km</option><option value="8">8 km</option>
    </select>
    <button type="button" class="btn ghost small" id="zl-circle">Draw circle</button>
    <button type="button" class="btn ghost small" id="zl-fit">Fit to boundary</button>
  </div>
  <p class="small muted" style="margin:8px 0 0">Click the map to add boundary points, drag the white dots to adjust them. The pin is the zone's centre: drag it, search an address or use your current location. {{ $mapsKey ? '' : 'Add a Google Maps key in Integrations to use Google Maps here.' }}</p>
  @error('polygon')<div class="zl-err">{{ $message }}</div>@enderror
  @endif

  <input type="hidden" name="country" id="zl-v-country">
  <input type="hidden" name="country_code" id="zl-v-code">
  <input type="hidden" name="state" id="zl-v-state">
  <input type="hidden" name="city" id="zl-v-city">
  <input type="hidden" name="{{ $latName }}" id="zl-v-lat" value="{{ $init['center'][0] ?? '' }}">
  <input type="hidden" name="{{ $lngName }}" id="zl-v-lng" value="{{ $init['center'][1] ?? '' }}">
  @unless($point)<textarea name="{{ $name }}" id="f_{{ $name }}" class="sr" aria-label="Boundary points">{{ json_encode($init['polygon']) }}</textarea>@endunless
</div>

@push('scripts')
@unless($mapsKey)<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>@endunless
<script>
(function () {
  var INIT = @json($init);
  var GOOGLE = @json((bool) $mapsKey);
  var OTHER = '__other__';
  var $ = function (id) { return document.getElementById(id); };
  var data = { countries: [] };
  var pts = (INIT.polygon || []).map(function (p) { return [+p[0], +p[1]]; });
  var center = INIT.center;
  var engine = null;
  var POINT = !!INIT.point;
  function radiusM() { var el = INIT.radiusInput && $(INIT.radiusInput); return el ? (+el.value || 0) * (el.dataset.km ? 1000 : 1) : 0; }

  function status(msg, tone) { var s = $('zl-status'); s.textContent = msg || ''; s.className = 'zl-status' + (tone ? ' ' + tone : ''); }
  function opt(sel, value, label, selected) { var o = document.createElement('option'); o.value = value; o.textContent = label; if (selected) o.selected = true; sel.appendChild(o); }
  function norm(s) { return String(s || '').toLowerCase().replace(/\(.*?\)/g, '').replace(/[^a-z]/g, ''); }

  // ---------- Country → State → City ----------
  function country() { return data.countries.find(function (c) { return c.code === $('zl-country').value; }) || null; }
  function states() { var c = country(); return c && c.states ? c.states : []; }
  function textMode(which, on, value) {
    $('zl-' + which).hidden = on && $('zl-' + which).options.length <= 1;
    $('zl-' + which + '-text').hidden = !on;
    if (on && value != null) $('zl-' + which + '-text').value = value;
  }
  function fillStates(selected) {
    var sel = $('zl-state'); sel.innerHTML = ''; var list = states();
    if (!list.length) { textMode('state', true, selected || ''); sel.hidden = true; fillCities(INIT.city); return sync(); }
    sel.hidden = false; opt(sel, '', 'Choose a state', !selected);
    var found = false;
    list.forEach(function (s) { var m = selected && norm(s.name) === norm(selected); if (m) found = true; opt(sel, s.name, s.name, m); });
    opt(sel, OTHER, 'Other (type it)', false);
    if (selected && !found) { sel.value = OTHER; textMode('state', true, selected); } else textMode('state', false);
    fillCities();
  }
  function fillCities(selected) {
    var sel = $('zl-city'); sel.innerHTML = '';
    var st = states().find(function (s) { return s.name === $('zl-state').value; });
    if (!st) { sel.hidden = true; textMode('city', true, selected != null ? selected : $('zl-city-text').value); return sync(); }
    sel.hidden = false; opt(sel, '', 'Choose a city', !selected);
    var found = false;
    st.cities.forEach(function (c) { var m = selected && norm(c) === norm(selected); if (m) found = true; opt(sel, c, c, m); });
    opt(sel, OTHER, 'Other (type it)', false);
    if (selected && !found) { sel.value = OTHER; textMode('city', true, selected); } else textMode('city', false);
    sync();
  }
  function val(which) { var sel = $('zl-' + which); return (!sel.hidden && sel.value && sel.value !== OTHER) ? sel.value : $('zl-' + which + '-text').value.trim(); }
  function sync() {
    var c = country();
    $('zl-v-country').value = c ? c.name : '';
    $('zl-v-code').value = c ? c.code : '';
    $('zl-v-state').value = val('state');
    $('zl-v-city').value = val('city');
    $('zl-v-lat').value = center ? center[0].toFixed(7) : '';
    $('zl-v-lng').value = center ? center[1].toFixed(7) : '';
    if (engine && engine.ring) engine.ring();
    if (POINT) return;
    $('f_{{ $name }}').value = JSON.stringify(pts.map(function (p) { return [+p[0].toFixed(6), +p[1].toFixed(6)]; }));
    $('zl-count').textContent = pts.length + ' boundary point' + (pts.length === 1 ? '' : 's') + (pts.length < 3 ? ' · need at least 3' : '');
  }
  function placeText() { return [$('zl-address').value, val('city'), val('state'), $('zl-pincode').value, country() && country().name].filter(Boolean).join(', '); }

  /** Fill the dropdowns from a geocoder result: {country, code, state, city, pincode, address}. */
  function applyPlace(p) {
    if (p.code && data.countries.some(function (c) { return c.code === p.code; })) { $('zl-country').value = p.code; }
    fillStates(p.state || '');
    if (p.city) fillCities(p.city);
    if (p.pincode) $('zl-pincode').value = p.pincode;
    if (p.address && !$('zl-address').value) $('zl-address').value = p.address;
    sync();
  }

  $('zl-country').addEventListener('change', function () { fillStates(''); lookCity(); });
  $('zl-state').addEventListener('change', function () { if (this.value === OTHER) { textMode('state', true, ''); $('zl-state-text').focus(); } else textMode('state', false); fillCities(); });
  $('zl-city').addEventListener('change', function () { if (this.value === OTHER) { textMode('city', true, ''); $('zl-city-text').focus(); } else textMode('city', false); sync(); lookCity(); });
  ['zl-state-text', 'zl-city-text', 'zl-pincode', 'zl-address'].forEach(function (id) { $(id).addEventListener('input', sync); });
  $('zl-address').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); find(); } });

  // ---------- Map engines ----------
  function circle(km) {
    if (!center) { status('Set the centre first: search an address, use your current location, or drag the pin.', 'bad'); return; }
    var out = []; var n = 32;
    for (var i = 0; i < n; i++) {
      var a = 2 * Math.PI * i / n;
      out.push([center[0] + (km / 111.32) * Math.cos(a), center[1] + (km / (111.32 * Math.cos(center[0] * Math.PI / 180))) * Math.sin(a)]);
    }
    pts = out; engine.draw(true); sync();
    status('Boundary set to a ' + km + ' km circle around the centre. Drag the dots to follow roads or rivers.', 'ok');
  }
  function setCenter(lat, lng, zoom) { center = [lat, lng]; engine.center(lat, lng, zoom); sync(); }

  var Google = {
    load: function (done) {
      window.__dtZoneMap = done;
      var s = document.createElement('script');
      s.src = 'https://maps.googleapis.com/maps/api/js?key={{ urlencode((string) $mapsKey) }}&v=weekly&loading=async&callback=__dtZoneMap';
      s.async = true; s.onerror = function () { status('Google Maps could not load. Check the Maps key in Integrations.', 'bad'); };
      document.head.appendChild(s);
    },
    init: function () {
      var g = google.maps, self = this;
      this.map = new g.Map($('zl-map'), { center: { lat: (center || pts[0] || [13.0067, 80.257])[0], lng: (center || pts[0] || [13.0067, 80.257])[1] }, zoom: 13, mapTypeControl: false, streetViewControl: false, clickableIcons: false, gestureHandling: 'greedy' });
      this.geocoder = new g.Geocoder();
      this.poly = new g.Polygon({ map: this.map, paths: [], editable: true, strokeColor: '#0F5C4D', strokeWeight: 3, fillColor: '#0F5C4D', fillOpacity: 0.18 });
      this.pin = new g.Marker({ map: this.map, draggable: true, title: 'Zone centre', position: center ? { lat: center[0], lng: center[1] } : null });
      this.pin.addListener('dragend', function (e) { center = [e.latLng.lat(), e.latLng.lng()]; sync(); });
      this.map.addListener('click', function (e) { if (POINT) { setCenter(e.latLng.lat(), e.latLng.lng()); return; } pts.push([e.latLng.lat(), e.latLng.lng()]); self.draw(false); sync(); });
      (INIT.overlays || []).forEach(function (o) {
        var pg = new g.Polygon({ map: self.map, paths: o.polygon.map(function (p) { return { lat: +p[0], lng: +p[1] }; }), clickable: false, strokeColor: o.mine ? '#7A3FF2' : '#9AA7A3', strokeWeight: 2, fillColor: o.mine ? '#7A3FF2' : '#9AA7A3', fillOpacity: o.mine ? 0.12 : 0.05 });
      });
      this.circleShape = new g.Circle({ map: this.map, clickable: false, strokeColor: '#167D49', strokeOpacity: 0.9, strokeWeight: 2, fillColor: '#34B57A', fillOpacity: 0.06 });
      this.draw(false);
      if (pts.length > 2) this.fit();
      this.ring();
    },
    ring: function () { if (!this.circleShape) return; var r = radiusM(); this.circleShape.setVisible(!!(center && r)); if (center && r) { this.circleShape.setCenter({ lat: center[0], lng: center[1] }); this.circleShape.setRadius(r); } },
    draw: function (fit) {
      if (POINT) return;
      var self = this, path = new google.maps.MVCArray(pts.map(function (p) { return new google.maps.LatLng(p[0], p[1]); }));
      this.poly.setPath(path);
      var read = function () { pts = self.poly.getPath().getArray().map(function (ll) { return [ll.lat(), ll.lng()]; }); sync(); };
      ['set_at', 'insert_at', 'remove_at'].forEach(function (ev) { google.maps.event.addListener(path, ev, read); });
      if (fit) this.fit();
    },
    fit: function () { if (!pts.length) return; var b = new google.maps.LatLngBounds(); pts.forEach(function (p) { b.extend({ lat: p[0], lng: p[1] }); }); this.map.fitBounds(b, 40); },
    center: function (lat, lng, zoom) { this.pin.setPosition({ lat: lat, lng: lng }); this.map.panTo({ lat: lat, lng: lng }); if (zoom) this.map.setZoom(zoom); },
    parse: function (r) {
      var get = function (t, short) { var c = (r.address_components || []).find(function (x) { return x.types.indexOf(t) >= 0; }); return c ? (short ? c.short_name : c.long_name) : ''; };
      return { code: get('country', true), country: get('country'), state: get('administrative_area_level_1'),
        city: get('locality') || get('administrative_area_level_3') || get('administrative_area_level_2'),
        pincode: get('postal_code'), address: (r.formatted_address || '').split(',').slice(0, 3).join(',').trim(),
        lat: r.geometry.location.lat(), lng: r.geometry.location.lng() };
    },
    search: function (q, cb) { var self = this; this.geocoder.geocode({ address: q }, function (res, st) { cb(st === 'OK' && res[0] ? self.parse(res[0]) : null); }); },
    reverse: function (lat, lng, cb) { var self = this; this.geocoder.geocode({ location: { lat: lat, lng: lng } }, function (res, st) { cb(st === 'OK' && res[0] ? self.parse(res[0]) : null); }); },
  };

  var Osm = {
    load: function (done) { done(); },
    init: function () {
      var self = this;
      this.map = L.map('zl-map').setView(center || pts[0] || [13.0067, 80.257], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this.map);
      this.poly = L.polygon([], { color: '#0F5C4D', weight: 3, fillOpacity: 0.18 }).addTo(this.map);
      this.dots = L.layerGroup().addTo(this.map);
      this.pin = L.marker(center || [13.0067, 80.257], { draggable: true, title: 'Zone centre' });
      if (center) this.pin.addTo(this.map);
      this.pin.on('dragend', function (e) { var p = e.target.getLatLng(); center = [p.lat, p.lng]; sync(); });
      this.map.on('click', function (e) { if (POINT) { setCenter(e.latlng.lat, e.latlng.lng); return; } pts.push([e.latlng.lat, e.latlng.lng]); self.draw(false); sync(); });
      (INIT.overlays || []).forEach(function (o) {
        L.polygon(o.polygon, { interactive: true, color: o.mine ? '#7A3FF2' : '#9AA7A3', weight: 2, fillOpacity: o.mine ? 0.12 : 0.05 }).bindTooltip(o.name).addTo(self.map);
      });
      this.circleShape = L.circle(center || [13.0067, 80.257], { radius: 1, interactive: false, color: '#167D49', weight: 2, dashArray: '6 6', fillOpacity: 0.05 });
      this.draw(false);
      if (pts.length > 2) this.fit();
      this.ring();
    },
    ring: function () { if (!this.circleShape) return; var r = radiusM(); if (center && r) { this.circleShape.setLatLng(center).setRadius(r).addTo(this.map); } else { this.circleShape.remove(); } },
    draw: function (fit) {
      if (POINT) return;
      var self = this; this.poly.setLatLngs(pts); this.dots.clearLayers();
      pts.forEach(function (p, i) {
        var m = L.marker(p, { draggable: true, icon: L.divIcon({ className: '', html: '<div class="zl-vx"></div>', iconSize: [0, 0] }) });
        m.on('drag', function (e) { var ll = e.target.getLatLng(); pts[i] = [ll.lat, ll.lng]; self.poly.setLatLngs(pts); sync(); });
        self.dots.addLayer(m);
      });
      if (fit) this.fit();
    },
    fit: function () { if (pts.length) this.map.fitBounds(L.latLngBounds(pts), { padding: [40, 40] }); },
    center: function (lat, lng, zoom) { this.pin.setLatLng([lat, lng]).addTo(this.map); this.map.setView([lat, lng], zoom || this.map.getZoom()); },
    parse: function (r) {
      var a = r.address || {};
      return { code: (a.country_code || '').toUpperCase(), country: a.country, state: a.state || a.union_territory || '',
        city: a.city || a.town || a.municipality || a.county || a.state_district || '', pincode: a.postcode || '',
        address: [a.house_number, a.road, a.neighbourhood || a.suburb].filter(Boolean).join(', ') || (r.display_name || '').split(',').slice(0, 3).join(','),
        lat: +r.lat, lng: +r.lon };
    },
    search: function (q, cb) { var self = this; fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=1&q=' + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(function (j) { cb(j[0] ? self.parse(j[0]) : null); }).catch(function () { cb(null); }); },
    reverse: function (lat, lng, cb) { var self = this; fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=' + lat + '&lon=' + lng).then(function (r) { return r.json(); }).then(function (j) { cb(j && j.address ? self.parse(j) : null); }).catch(function () { cb(null); }); },
  };

  // ---------- Search, current location, city lookup ----------
  function find() {
    var q = placeText();
    if (!q) { status('Type an address or choose a city first.', 'bad'); return; }
    status('Searching…');
    engine.search(q, function (p) {
      if (!p) { status('Address not found. Try a nearby landmark or the area name.', 'bad'); return; }
      setCenter(p.lat, p.lng, 15);
      if (p.pincode && !$('zl-pincode').value) $('zl-pincode').value = p.pincode;
      sync(); status(POINT ? 'Found. Drag the pin to the exact door if needed.' : 'Found. Drag the pin if needed, then draw the boundary or use "Draw circle".', 'ok');
    });
  }
  function lookCity() {
    if (center || pts.length || !val('city')) return;
    engine.search([val('city'), val('state'), country() && country().name].filter(Boolean).join(', '), function (p) { if (p) engine.center(p.lat, p.lng, 12); });
  }
  $('zl-find').addEventListener('click', find);
  $('zl-here').addEventListener('click', function () {
    if (!navigator.geolocation) { status('This browser cannot share your location.', 'bad'); return; }
    status('Getting your location…');
    navigator.geolocation.getCurrentPosition(function (pos) {
      var lat = pos.coords.latitude, lng = pos.coords.longitude;
      setCenter(lat, lng, 16);
      engine.reverse(lat, lng, function (p) {
        if (p) { $('zl-address').value = p.address || $('zl-address').value; applyPlace(p); }
        status('Location found (within ' + Math.round(pos.coords.accuracy) + ' m). Country, state, city and address filled in.', 'ok');
      });
    }, function (err) {
      status(err.code === 1 ? 'Location permission was blocked. Allow location for this site in the browser, or search the address.' : 'Could not get your location. Try again or search the address.', 'bad');
    }, { enableHighAccuracy: true, timeout: 15000 });
  });
  if (!POINT) {
    $('zl-undo').addEventListener('click', function () { pts.pop(); engine.draw(false); sync(); });
    $('zl-clear').addEventListener('click', function () { pts = []; engine.draw(false); sync(); });
    $('zl-circle').addEventListener('click', function () { circle(+$('zl-radius').value); });
    $('zl-fit').addEventListener('click', function () { engine.fit(); });
  }
  if (INIT.radiusInput && $(INIT.radiusInput)) $(INIT.radiusInput).addEventListener('input', function () { if (engine && engine.ring) engine.ring(); });
  window.dtLocation = { center: function () { return center; } };

  // ---------- Start ----------
  fetch($('zl').dataset.locations).then(function (r) { return r.json(); }).then(function (j) {
    data = j;
    var sel = $('zl-country');
    data.countries.forEach(function (c) { opt(sel, c.code, c.name, c.code === (INIT.country_code || data.default)); });
    fillStates(INIT.state);
    fillCities(INIT.city);
    if (!$('zl-v-city').value && INIT.city) { textMode('city', true, INIT.city); sync(); }
  }).catch(function () { status('Could not load the country list. Type the state and city.', 'bad'); textMode('state', true, INIT.state); textMode('city', true, INIT.city); });

  engine = GOOGLE ? Google : Osm;
  engine.load(function () {
    try { engine.init(); sync(); lookCity(); engine.ready = true; }
    catch (e) { status('The map could not load (check the internet connection or the Maps key). The dropdowns and address still work.', 'bad'); }
  });
  sync();
})();
</script>
@endpush
