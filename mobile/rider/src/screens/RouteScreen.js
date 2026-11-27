import React, { useCallback, useState } from 'react';
import { Linking, Platform, Pressable, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api, clock } from '../shared/api';
import { Backdrop, Glass, IconBtn, Muted } from '../shared/ui';
import Icon from '../shared/Icons';
import MapView from '../shared/MapView';
import { BLOBS, km, R } from '../rt';

export function openMaps(a) {
  if (!a) return;
  const q = a.lat != null && a.lng != null ? `${a.lat},${a.lng}` : encodeURIComponent([a.flat, a.building, a.street, a.area, a.city, a.pincode].filter(Boolean).join(', '));
  Linking.openURL(Platform.OS === 'ios' ? `http://maps.apple.com/?daddr=${q}` : `google.navigation:q=${q}`).catch(() => Linking.openURL(`https://www.google.com/maps/dir/?api=1&destination=${q}`));
}

export default function RouteScreen({ navigation }) {
  const insets = useSafeAreaInsets();
  const [b, setB] = useState(undefined);
  const load = useCallback(async () => { try { const d = await api('/rider/today'); setB(d.batches.find((x) => x.status === 'picked_up') || d.batches.find((x) => x.status === 'accepted') || null); } catch (e) {} }, []);
  useFocusEffect(useCallback(() => { load(); const t = setInterval(load, 20000); return () => clearInterval(t); }, [load]));

  if (b === undefined) return <View style={{ flex: 1, backgroundColor: R.bg }} />;
  if (!b || b.status !== 'picked_up') return (
    <View style={{ flex: 1, backgroundColor: R.bg, padding: 24, paddingTop: insets.top + 24, gap: 12 }}>
      <Backdrop colors={BLOBS} />
      <Text style={{ fontSize: 24, fontWeight: '800', color: R.ink }}>Route</Text>
      <Glass style={{ borderRadius: 24, padding: 20, gap: 8 }}>
        <Text style={{ fontSize: 17, fontWeight: '800', color: R.ink }}>{b ? 'Pick up your batch first' : 'No route yet'}</Text>
        <Muted>{b ? `Show code ${b.pickup_code} at ${b.kitchen?.name} and check every package. The route opens after pickup.` : 'Accept a batch on the Today tab. Your stops and map show up here after pickup.'}</Muted>
        {b ? <Pressable onPress={() => navigation.navigate('Pickup', { id: b.id })}><Text style={{ color: R.blue, fontWeight: '800', marginTop: 6 }}>Open pickup checklist ›</Text></Pressable> : null}
      </Glass>
    </View>
  );

  const open = b.stops.filter((s) => !['delivered', 'failed'].includes(s.status));
  const next = open[0];
  const idx = next ? b.stops.indexOf(next) : b.stops.length;
  const prev = idx > 0 ? b.stops[idx - 1].address : { lat: b.kitchen?.lat, lng: b.kitchen?.lng };
  const leg = next ? km([{ lat: +prev?.lat, lng: +prev?.lng }, { lat: +next.address?.lat, lng: +next.address?.lng }]) : 0;
  const points = [{ lat: +b.kitchen?.lat, lng: +b.kitchen?.lng, label: 'K', kind: 'k' }, ...b.stops.map((s) => ({ lat: s.address?.lat != null ? +s.address.lat : null, lng: s.address?.lng != null ? +s.address.lng : null, label: s.status === 'delivered' ? '✓' : String(s.sequence), kind: s.status === 'delivered' ? 'd' : '' }))];
  const a = next?.address || {};

  return (
    <View style={{ flex: 1, backgroundColor: R.bg }}>
      <MapView mode="route" points={points} lat={points[0].lat || 13.0067} lng={points[0].lng || 80.2573} zoom={14} style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, borderRadius: 0 }} />
      <View style={{ position: 'absolute', top: insets.top + 10, left: 16, right: 16 }}>
        <Glass strong style={{ borderRadius: 22, padding: 10, flexDirection: 'row', alignItems: 'center', gap: 10 }}>
          <IconBtn label="Today" onPress={() => navigation.navigate('Today')} icon={<Icon name="back" color={R.ink} />} />
          <View style={{ flex: 1 }}>
            <Text style={{ fontWeight: '800', fontSize: 16, color: R.ink }}>Batch {b.code} · {next ? `stop ${idx + 1} of ${b.stops.length}` : 'all done'}</Text>
            {next ? <Text style={{ fontSize: 13, color: R.muted }}>{leg.toFixed(1)} km · about {Math.max(2, Math.round(leg * 4))} min</Text> : null}
          </View>
        </Glass>
      </View>

      <Glass strong style={{ position: 'absolute', left: 0, right: 0, bottom: 0, borderTopLeftRadius: 30, borderTopRightRadius: 30, borderRadius: 0, padding: 20, paddingBottom: 100 + insets.bottom, gap: 10 }}>
        <View style={{ alignSelf: 'center', width: 40, height: 4, borderRadius: 2, backgroundColor: 'rgba(19,50,79,0.2)', marginTop: -8 }} />
        {next ? (
          <>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
              <Text style={{ fontSize: 12, fontWeight: '800', color: R.blue, letterSpacing: 0.6 }}>NEXT · {next.order_number}</Text>
              {next.slot ? <Text style={{ fontSize: 12, fontWeight: '800', color: '#0F5C4D', backgroundColor: '#E3F1EC', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999, overflow: 'hidden' }}>Due by {clock(next.slot.split('-')[1], true)} PM</Text> : null}
            </View>
            <Text style={{ fontSize: 22, fontWeight: '800', color: R.ink }}>{next.customer}</Text>
            <Text style={{ fontSize: 14, color: R.muted, lineHeight: 20 }}>{[a.flat, a.building, a.street, a.area].filter(Boolean).join(', ')}{a.landmark ? ` · near ${a.landmark}` : ''}</Text>
            {a.instructions ? <View style={{ backgroundColor: R.note, borderRadius: 14, padding: 10 }}><Text style={{ color: R.noteInk, fontSize: 13 }}><Text style={{ fontWeight: '800' }}>Note: </Text>{a.instructions}</Text></View> : null}
            {next.handling.map((h) => <Text key={h} style={{ color: R.badInk, fontWeight: '700', fontSize: 12 }}>⚠ {h}</Text>)}
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Pressable disabled={!next.phone} onPress={() => Linking.openURL('tel:' + next.phone)} accessibilityRole="button" style={{ flex: 1, minHeight: 48, borderRadius: 999, borderWidth: 1.5, borderColor: 'rgba(19,50,79,0.18)', backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center', opacity: next.phone ? 1 : 0.5 }}><Text style={{ fontWeight: '800', color: R.ink }}>Call</Text></Pressable>
              <Pressable onPress={() => openMaps(a)} accessibilityRole="button" style={{ flex: 1, minHeight: 48, borderRadius: 999, borderWidth: 1.5, borderColor: 'rgba(19,50,79,0.18)', backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: R.ink }}>Navigate</Text></Pressable>
            </View>
            <Pressable onPress={() => navigation.navigate('Deliver', { stop: next })} accessibilityRole="button" style={{ minHeight: 50, borderRadius: 999, backgroundColor: R.blue, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: '#fff', fontSize: 16 }}>I've arrived</Text></Pressable>
            {open.length > 1 ? <Text style={{ fontSize: 13, color: R.muted }}>Then: {open.slice(1).map((s) => `${s.sequence} · ${s.address?.area || s.customer}`).join(' · ')}</Text> : null}
          </>
        ) : (
          <>
            <Text style={{ fontSize: 22, fontWeight: '800', color: R.ink }}>All stops done 🎉</Text>
            <Muted>{b.stops.filter((s) => s.status === 'delivered').length} delivered{b.stops.some((s) => s.status === 'failed') ? ' · problems reported to the kitchen' : ''}.</Muted>
            <Pressable onPress={() => navigation.navigate('Today')} style={{ minHeight: 50, borderRadius: 999, backgroundColor: R.green, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: '#fff', fontSize: 16 }}>Back to Today</Text></Pressable>
          </>
        )}
      </Glass>
    </View>
  );
}
