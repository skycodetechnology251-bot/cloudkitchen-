import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, AppState, Pressable, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { useFocusEffect } from '@react-navigation/native';
import { api, slotLabel } from '../shared/api';
import { Glass, IconBtn, Muted, Rise, Toggle } from '../shared/ui';
import Icon from '../shared/Icons';
import { Screen } from './_Screen';
import { useRider } from '../rider';
import { km, pts, R } from '../rt';

function Offer({ b, onDone }) {
  const [left, setLeft] = useState(b.offer_seconds_left ?? 45);
  const [busy, setBusy] = useState(false);
  useEffect(() => { const t = setInterval(() => setLeft((s) => Math.max(0, s - 1)), 1000); return () => clearInterval(t); }, []);
  useEffect(() => { if (left === 0) onDone(); }, [left]);
  const respond = async (accept) => { try { setBusy(true); await api(`/rider/batches/${b.id}/respond`, { method: 'POST', body: { accept } }); } catch (e) { Alert.alert('Could not respond', e.message); } finally { setBusy(false); onDone(); } };
  const areas = [...new Set(b.stops.map((s) => s.address?.area).filter(Boolean))].join(', ');
  const slot = b.stops.find((s) => s.slot)?.slot;
  return (
    <View style={{ backgroundColor: R.amberBg, borderRadius: 24, borderWidth: 2, borderColor: R.amber, padding: 16, gap: 8 }}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
        <Text style={{ fontWeight: '800', color: '#8A5A00', letterSpacing: 0.6, fontSize: 12 }}>NEW BATCH · {b.code}</Text>
        <Text accessibilityLiveRegion="polite" style={{ fontWeight: '800', fontSize: 12, color: R.badgeInk, backgroundColor: R.amber, paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999, overflow: 'hidden' }}>Accept in 0:{String(left).padStart(2, '0')}</Text>
      </View>
      <Text style={{ fontSize: 22, fontWeight: '800', color: R.ink }}>{b.stops.length} order{b.stops.length === 1 ? '' : 's'} · {km(pts(b)).toFixed(1)} km</Text>
      <Text style={{ fontSize: 14, color: R.muted, lineHeight: 20 }}>Pick up at <Text style={{ fontWeight: '800', color: R.ink }}>{b.kitchen?.name}</Text>{'\n'}Deliver {slot ? slotLabel(slot) : 'at lunchtime'}{areas ? ` · ${areas}` : ''}</Text>
      <View style={{ flexDirection: 'row', gap: 10, marginTop: 4 }}>
        <Pressable onPress={() => respond(false)} disabled={busy} accessibilityRole="button" style={{ flex: 1, minHeight: 50, borderRadius: 999, borderWidth: 1.5, borderColor: 'rgba(19,50,79,0.2)', backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: R.ink, fontSize: 15 }}>Decline</Text></Pressable>
        <Pressable onPress={() => respond(true)} disabled={busy} accessibilityRole="button" style={{ flex: 1.4, minHeight: 50, borderRadius: 999, backgroundColor: R.blue, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: '#fff', fontSize: 15 }}>Accept batch</Text></Pressable>
      </View>
    </View>
  );
}

function Stat({ label, value }) {
  return <Glass style={{ flex: 1, borderRadius: 20, padding: 14 }}><Text style={{ fontSize: 12, color: R.muted, fontWeight: '600' }}>{label}</Text><Text style={{ fontSize: 26, fontWeight: '800', color: R.ink, marginTop: 2 }}>{value}</Text></Glass>;
}

export default function HomeScreen({ navigation }) {
  const { user } = useRider();
  const [data, setData] = useState(null);
  const [busy, setBusy] = useState(false);
  const [since, setSince] = useState(null);
  const watch = useRef(null);

  const load = useCallback(async () => { setBusy(true); try { setData(await api('/rider/today')); } catch (e) {} finally { setBusy(false); } }, []);
  useFocusEffect(useCallback(() => { load(); const t = setInterval(load, 15000); return () => clearInterval(t); }, [load]));

  /** Share location every ~30 s / 50 m while on duty and the app is open. */
  const startTracking = async () => {
    const { status } = await Location.requestForegroundPermissionsAsync();
    if (status !== 'granted') { Alert.alert('Location needed', 'Turn on location so the kitchen can assign deliveries near you.'); return false; }
    watch.current?.remove();
    watch.current = await Location.watchPositionAsync({ accuracy: Location.Accuracy.Balanced, timeInterval: 30000, distanceInterval: 50 },
      (p) => api('/rider/location', { method: 'POST', body: { lat: p.coords.latitude, lng: p.coords.longitude } }).catch(() => {}));
    setSince((s) => s || new Date());
    return true;
  };
  const stopTracking = () => { watch.current?.remove(); watch.current = null; setSince(null); };
  useEffect(() => { if (data?.on_duty && !watch.current) startTracking(); if (data && !data.on_duty) stopTracking(); }, [data?.on_duty]);
  useEffect(() => { const sub = AppState.addEventListener('change', (s) => { if (s === 'active') load(); }); return () => { sub.remove(); stopTracking(); }; }, []);

  const setDuty = async (v) => {
    if (v && !(await startTracking())) return;
    if (!v) stopTracking();
    try { await api('/rider/duty', { method: 'POST', body: { on_duty: v } }); load(); } catch (e) { Alert.alert('Could not update', e.message); }
  };

  const offers = (data?.batches || []).filter((b) => b.status === 'offered');
  const active = (data?.batches || []).filter((b) => ['accepted', 'picked_up'].includes(b.status));
  const stopsLeft = active.reduce((a, b) => a + b.stops.filter((s) => !['delivered', 'failed'].includes(s.status)).length, 0);
  const initials = (user?.name || '').split(' ').map((x) => x[0]).join('').slice(0, 2).toUpperCase();
  const kitchen = (data?.batches || [])[0]?.kitchen?.name;

  return (
    <Screen refreshing={busy} onRefresh={load}>
      <Rise index={0} style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: R.badge, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: R.badgeInk }}>{initials}</Text></View>
        <View style={{ flex: 1 }}>
          <Text style={{ fontSize: 18, fontWeight: '800', color: R.ink }}>Hi, {(user?.name || '').split(' ')[0]}</Text>
          <Text style={{ fontSize: 13, color: R.muted, fontWeight: '600' }}>Delivery partner{kitchen ? ` · ${kitchen}` : ''}</Text>
        </View>
        <IconBtn label="Refresh" onPress={load} icon={<Icon name="bell" color={R.ink} />} />
      </Rise>

      <Rise index={1}>
        <View style={{ backgroundColor: data?.on_duty ? R.green : '#fff', borderRadius: 24, paddingHorizontal: 16, paddingVertical: 8 }}>
          <Toggle value={!!data?.on_duty} onChange={setDuty} label={data?.on_duty ? 'On duty' : 'Off duty'}
            note={data?.on_duty ? `Sharing location while on duty${since ? ' · since ' + since.toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit' }).toUpperCase() : ''}` : 'Go on duty to receive delivery batches.'} />
        </View>
      </Rise>

      {offers.map((b) => <Rise key={b.id} index={2}><Offer b={b} onDone={load} /></Rise>)}

      {active.map((b, i) => (
        <Rise key={b.id} index={3 + i}>
          <Pressable onPress={() => (b.status === 'accepted' ? navigation.navigate('Pickup', { id: b.id }) : navigation.navigate('Route'))} accessibilityRole="button" accessibilityLabel={`Batch ${b.code}`}>
            <Glass style={{ borderRadius: 24, padding: 16, gap: 6, borderWidth: 2, borderColor: R.blue }}>
              <Text style={{ fontSize: 12, fontWeight: '800', color: R.blue, letterSpacing: 0.6 }}>{b.status === 'accepted' ? `BATCH ${b.code} · GO TO KITCHEN` : `BATCH ${b.code} · OUT FOR DELIVERY`}</Text>
              <Text style={{ fontSize: 20, fontWeight: '800', color: R.ink }}>{b.status === 'accepted' ? `Pick up at ${b.kitchen?.name}` : `${b.stops.filter((s) => s.status === 'delivered').length} of ${b.stops.length} delivered`}</Text>
              <Text style={{ color: R.blue, fontWeight: '800' }}>{b.status === 'accepted' ? 'Open pickup checklist ›' : 'Open route ›'}</Text>
            </Glass>
          </Pressable>
        </Rise>
      ))}

      <Rise index={5} style={{ flexDirection: 'row', gap: 10 }}>
        <Stat label="Delivered today" value={data?.delivered_today ?? 0} />
        <Stat label="Stops left" value={stopsLeft} />
      </Rise>

      {data && !offers.length && !active.length ? <Glass style={{ padding: 20, alignItems: 'center', gap: 6, borderRadius: 24 }}><Text style={{ fontSize: 30 }}>🍱</Text><Text style={{ fontSize: 17, fontWeight: '800', color: R.ink }}>No batches right now</Text><Muted style={{ textAlign: 'center' }}>{data.on_duty ? 'Stay near the kitchen around noon. New batches show up here with a sound.' : 'Go on duty to start.'}</Muted></Glass> : null}
      <Muted style={{ fontSize: 12 }}>You only see addresses and handling notes for orders assigned to you. Customer health details are never shared.</Muted>
    </Screen>
  );
}
