import React, { forwardRef, useImperativeHandle, useMemo, useRef } from 'react';
import { View } from 'react-native';
import { WebView } from 'react-native-webview';

/**
 * Map without a Google key: Leaflet + OpenStreetMap tiles inside a WebView.
 * mode "pick": a fixed pin in the middle; dragging the map moves the spot (onMove({lat,lng})).
 * mode "route": markers (K = kitchen, numbers = stops) joined by a line.
 */
const MapView = forwardRef(function MapView({ lat = 13.0067, lng = 80.2573, zoom = 17, mode = 'pick', points = [], onMove, style }, ref) {
  const web = useRef(null);
  useImperativeHandle(ref, () => ({ moveTo: (a, b) => web.current?.injectJavaScript(`window.moveTo(${+a},${+b});true;`) }));
  const html = useMemo(() => `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>html,body,#m{margin:0;height:100%;background:#E9EFEC;font-family:-apple-system,Roboto,sans-serif}
.pin{position:absolute;left:50%;top:50%;transform:translate(-50%,-100%);z-index:999;pointer-events:none}
.mk{width:26px;height:26px;border-radius:50%;background:#1F5FA8;color:#fff;font:800 12px/26px sans-serif;text-align:center;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)}
.mk.k{background:#13324F;border-radius:7px}.mk.d{background:#1E8A5A}
.leaflet-control-attribution{font-size:9px}</style></head><body><div id="m"></div>
${mode === 'pick' ? '<svg class="pin" width="40" height="48" viewBox="0 0 40 48"><path d="M20 47S4 30 4 18a16 16 0 0 1 32 0c0 12-16 29-16 29Z" fill="#0F5C4D" stroke="#fff" stroke-width="2"/><circle cx="20" cy="18" r="6" fill="#fff"/></svg>' : ''}
<script>
var map=L.map('m',{zoomControl:false,attributionControl:true}).setView([${lat},${lng}],${zoom});
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);
function post(o){window.ReactNativeWebView&&window.ReactNativeWebView.postMessage(JSON.stringify(o));}
window.moveTo=function(a,b){map.setView([a,b],Math.max(map.getZoom(),17));};
${mode === 'pick' ? "map.on('moveend',function(){var c=map.getCenter();post({lat:c.lat,lng:c.lng});});" : ''}
${mode === 'route' ? `var pts=${JSON.stringify(points)};var ll=[];pts.forEach(function(p){if(p.lat==null)return;ll.push([p.lat,p.lng]);L.marker([p.lat,p.lng],{icon:L.divIcon({className:'',html:'<div class="mk '+(p.kind||'')+'">'+p.label+'</div>',iconSize:[26,26],iconAnchor:[13,13]})}).addTo(map);});
if(ll.length>1){L.polyline(ll,{color:'#1F5FA8',weight:4,opacity:.85}).addTo(map);map.fitBounds(ll,{padding:[40,40]});}` : ''}
</script></body></html>`, [mode, JSON.stringify(points)]);

  return (
    <View style={[{ overflow: 'hidden', borderRadius: 20, backgroundColor: '#E9EFEC' }, style]}>
      <WebView ref={web} originWhitelist={['*']} source={{ html }} javaScriptEnabled scrollEnabled={false} nestedScrollEnabled
        onMessage={(e) => { try { const m = JSON.parse(e.nativeEvent.data); if (m.lat != null) onMove?.(m); } catch (x) {} }} />
    </View>
  );
});

export default MapView;
