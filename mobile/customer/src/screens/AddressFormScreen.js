import React, { useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { api } from '../shared/api';
import { Btn, Field, Glass, Muted, Rise, Toggle } from '../shared/ui';
import Icon from '../shared/Icons';
import MapView from '../shared/MapView';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useCart } from '../cart';

const EMPTY = { label: 'Home', flat: '', building: '', street: '', area: '', landmark: '', city: 'Chennai', state: 'Tamil Nadu', country: 'India', pincode: '', lat: null, lng: null, receiver_name: '', receiver_phone: '', instructions: '', is_default: false };
const LABELS = ['Home', 'Work', 'Family', 'Other'];

export default function AddressFormScreen({ navigation, route }) {
  const editing = route.params?.address;
  const cart = useCart();
  const map = useRef(null);
  const geoTimer = useRef(null);
  const [f, setF] = useState(editing ? { ...EMPTY, ...Object.fromEntries(Object.entries(editing).filter(([k]) => k in EMPTY).map(([k, v]) => [k, v ?? ''])) } : EMPTY);
  const [status, setStatus] = useState('');
  const [locating, setLocating] = useState(false);
  const [errors, setErrors] = useState({});
  const [busy, setBusy] = useState(false);
  const up = (k) => (v) => setF((s) => ({ ...s, [k]: v }));
  const start = editing ? { lat: +editing.lat, lng: +editing.lng } : { lat: 13.0067, lng: 80.2573 };

  /** After the pin settles: fill street / area / city / pincode (only empty fields) and check delivery. */
  const onPin = ({ lat, lng }) => {
    setF((s) => ({ ...s, lat, lng }));
    clearTimeout(geoTimer.current);
    geoTimer.current = setTimeout(async () => {
      try {
        const [g] = await Location.reverseGeocodeAsync({ latitude: lat, longitude: lng });
        if (g) setF((s) => ({ ...s, street: s.street || [g.streetNumber, g.street].filter(Boolean).join(' ') || g.name || '', area: s.area || g.district || g.subregion || '', city: g.city || s.city, state: g.region || s.state, country: g.country || s.country, pincode: s.pincode || g.postalCode || '' }));
      } catch (e) {}
      try {
        const sv = await api('/serviceability', { method: 'POST', body: { lat, lng } });
        setStatus(sv.serviceable ? (sv.zone.status === 'live' ? `${sv.zone.name} — we deliver here` : `${sv.zone.name} — opening soon`) : 'Not in our delivery area yet');
      } catch (e) {}
    }, 600);
  };
  useEffect(() => () => clearTimeout(geoTimer.current), []);

  const locate = async () => {
    try {
      setLocating(true);
      const { status: p } = await Location.requestForegroundPermissionsAsync();
      if (p !== 'granted') { setStatus('Location permission is off. Drag the map to your door instead.'); return; }
      const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      map.current?.moveTo(pos.coords.latitude, pos.coords.longitude);
      onPin({ lat: pos.coords.latitude, lng: pos.coords.longitude });
    } catch (e) { setStatus('Could not get your location. Move near a window and try again.'); } finally { setLocating(false); }
  };
  useEffect(() => { if (!editing) locate(); }, []);

  const save = async () => {
    if (f.lat == null) { setErrors({ lat: 'Place the pin on your door first.' }); return; }
    try {
      setBusy(true); setErrors({});
      const body = { ...f, receiver_phone: f.receiver_phone || null, is_default: !!f.is_default };
      const a = editing ? await api(`/addresses/${editing.id}`, { method: 'PUT', body }) : await api('/addresses', { method: 'POST', body });
      if (!editing) { if (cart.count) cart.clear(); cart.setAddressId(a.id); }
      navigation.goBack();
    } catch (e) { setErrors({ ...Object.fromEntries(Object.entries(e.errors || {}).map(([k, v]) => [k, v[0]])), _: e.message }); } finally { setBusy(false); }
  };

  return (
    <Screen footer={<>{errors._ ? <Text style={{ color: C.badInk, fontWeight: '700' }}>{errors._}</Text> : null}<Btn title={busy ? 'Saving…' : 'Save address'} onPress={save} disabled={busy} /></>}>
      <Header title={editing ? 'Edit address' : 'Add a new address'} onBack={() => navigation.goBack()} />
      <Rise index={0}>
        <Glass style={{ borderRadius: 26, padding: 14, gap: 12 }}>
          <View style={{ height: 220 }}>
            <MapView ref={map} lat={start.lat} lng={start.lng} onMove={onPin} style={{ flex: 1 }} />
            <View pointerEvents="none" style={{ position: 'absolute', left: 10, right: 10, bottom: 10, alignItems: 'center' }}>
              <Text style={{ backgroundColor: 'rgba(255,255,255,0.92)', fontSize: 12, fontWeight: '800', color: C.ink, paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999, overflow: 'hidden' }}>Drag the map to place the pin on your door</Text>
            </View>
          </View>
          {status ? <Text style={{ fontWeight: '700', color: /deliver here/.test(status) ? C.teal : C.warnInk }} accessibilityLiveRegion="polite">{status}</Text> : null}
          {errors.lat ? <Text style={{ color: C.badInk, fontWeight: '700' }}>{errors.lat}</Text> : null}
          <Btn kind="dark" title={locating ? 'Locating…' : 'Use my current location'} icon={locating ? <ActivityIndicator color="#fff" /> : <Icon name="target" size={18} color="#fff" />} onPress={locate} disabled={locating} />
          <Text style={{ fontSize: 13, fontWeight: '800', color: '#27403A' }}>Save as</Text>
          <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }} accessibilityRole="radiogroup">
            {LABELS.map((l) => <Pressable key={l} onPress={() => up('label')(l)} accessibilityRole="radio" accessibilityState={{ selected: f.label === l }} style={{ height: 40, paddingHorizontal: 16, borderRadius: 999, justifyContent: 'center', backgroundColor: f.label === l ? C.teal : 'rgba(255,255,255,0.85)', borderWidth: f.label === l ? 0 : 1, borderColor: 'rgba(18,48,42,0.1)' }}><Text style={{ fontWeight: '800', color: f.label === l ? '#fff' : C.ink }}>{l}</Text></Pressable>)}
          </View>
        </Glass>
      </Rise>

      <Rise index={1} style={{ gap: 12 }}>
        <Field label="Flat / house no. *" value={f.flat} onChangeText={up('flat')} error={errors.flat} placeholder="Flat 3B" />
        <Field label="Building / apartment" value={f.building} onChangeText={up('building')} placeholder="Lotus Apartments" />
        <Field label="Street *" value={f.street} onChangeText={up('street')} error={errors.street} placeholder="4th Main Rd" />
        <Field label="Area / locality *" value={f.area} onChangeText={up('area')} error={errors.area} placeholder="Gandhi Nagar, Adyar" />
        <Field label="Landmark" value={f.landmark} onChangeText={up('landmark')} placeholder="Near Adyar Ananda Bhavan" />
        <View style={{ flexDirection: 'row', gap: 10 }}>
          <View style={{ flex: 1 }}><Field label="City *" value={f.city} onChangeText={up('city')} error={errors.city} /></View>
          <View style={{ flex: 1 }}><Field label="Pincode *" value={f.pincode} onChangeText={(t) => up('pincode')(t.replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" error={errors.pincode} /></View>
        </View>
        <View style={{ flexDirection: 'row', gap: 10 }}>
          <View style={{ flex: 1 }}><Field label="State *" value={f.state} onChangeText={up('state')} error={errors.state} /></View>
          <View style={{ flex: 1 }}><Field label="Country *" value={f.country} onChangeText={up('country')} error={errors.country} /></View>
        </View>
      </Rise>

      <Rise index={2} style={{ gap: 12 }}>
        <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink, marginTop: 4 }}>For the rider</Text>
        <Field label="Receiver name (if not you)" value={f.receiver_name} onChangeText={up('receiver_name')} />
        <Field label="Receiver phone" value={f.receiver_phone} onChangeText={(t) => up('receiver_phone')(t.replace(/\D/g, '').slice(0, 10))} keyboardType="number-pad" error={errors.receiver_phone} />
        <Field label="Instructions" value={f.instructions} onChangeText={up('instructions')} placeholder="Ring once, leave with security if no answer" />
        <Toggle value={!!f.is_default} onChange={up('is_default')} label="Make this my default address" />
      </Rise>
    </Screen>
  );
}
