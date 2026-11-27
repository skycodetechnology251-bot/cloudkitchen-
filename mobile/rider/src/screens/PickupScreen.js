import React, { useCallback, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api } from '../shared/api';
import { Btn, Glass, Muted, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import { Header, Screen } from './_Screen';
import { R } from '../rt';

export default function PickupScreen({ navigation, route }) {
  const [b, setB] = useState(null);
  const [checked, setChecked] = useState([]);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const load = useCallback(async () => {
    try {
      const d = await api('/rider/today'); const x = d.batches.find((y) => y.id === route.params.id);
      if (!x) navigation.goBack(); else if (x.status === 'picked_up') navigation.replace('Main', { screen: 'Route' }); else setB(x);
    } catch (e) {}
  }, [route.params.id]);
  useFocusEffect(useCallback(() => { load(); }, [load]));
  if (!b) return <Screen><Header title="Pickup" onBack={() => navigation.goBack()} /></Screen>;

  const toggle = (id) => setChecked((c) => (c.includes(id) ? c.filter((x) => x !== id) : [...c, id]));
  const all = checked.length === b.stops.length;
  const pickup = async () => {
    try { setBusy(true); setError(''); await api(`/rider/batches/${b.id}/pickup`, { method: 'POST', body: { code: b.pickup_code, order_ids: checked } }); navigation.replace('Main', { screen: 'Route' }); }
    catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  return (
    <Screen footer={<>{error ? <Text style={{ color: R.badInk, fontWeight: '700' }}>{error}</Text> : null}<Btn accent={R.blue} title={busy ? 'Confirming…' : all ? 'Confirm pickup' : 'Check all packages to continue'} onPress={pickup} disabled={busy || !all} /></>}>
      <Header kicker={`BATCH ${b.code} · STEP 1 OF 2`} title={`Pick up at ${b.kitchen?.name}`} onBack={() => navigation.goBack()} />
      <Rise index={0}>
        <View style={{ backgroundColor: R.blue, borderRadius: 26, padding: 20, alignItems: 'center', gap: 4 }}>
          <Text style={{ color: R.blueSoft, fontWeight: '800', fontSize: 14 }}>Show this code at the counter</Text>
          <Text accessibilityLabel={`Counter code ${String(b.pickup_code || '').split('').join(' ')}`} style={{ color: '#fff', fontSize: 52, fontWeight: '800', letterSpacing: 14 }}>{b.pickup_code || '—'}</Text>
          <Text style={{ color: R.blueSoft, fontSize: 13 }}>{b.stops.length} package{b.stops.length === 1 ? '' : 's'} · the kitchen screen shows the same code</Text>
        </View>
      </Rise>
      <Rise index={1}>
        <Glass style={{ borderRadius: 24, padding: 16 }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 }}>
            <Text style={{ fontSize: 17, fontWeight: '800', color: R.ink }}>Packages</Text>
            <Text style={{ fontWeight: '800', color: R.blue }}>{checked.length} of {b.stops.length} checked</Text>
          </View>
          {b.stops.map((s, i) => {
            const on = checked.includes(s.order_id);
            const allergy = s.handling.some((h) => /allerg/i.test(h));
            return (
              <Pressable key={s.id} onPress={() => toggle(s.order_id)} accessibilityRole="checkbox" accessibilityState={{ checked: on }} accessibilityLabel={`${s.order_number}, ${s.items.join(', ')}`}
                style={{ flexDirection: 'row', gap: 12, alignItems: 'center', paddingVertical: 12, borderTopWidth: i ? 1 : 0, borderTopColor: 'rgba(19,50,79,0.08)' }}>
                <View style={{ width: 30, height: 30, borderRadius: 8, backgroundColor: on ? R.green : '#fff', borderWidth: on ? 0 : 1.5, borderColor: 'rgba(19,50,79,0.25)', alignItems: 'center', justifyContent: 'center' }}>{on ? <Icon name="check" size={16} color="#fff" width={3} /> : null}</View>
                <View style={{ flex: 1 }}>
                  <Text style={{ fontWeight: '800', color: R.ink, fontSize: 15 }}>{s.order_number} · {s.items.join(', ')}</Text>
                  <Text style={{ fontSize: 13, fontWeight: '700', color: allergy ? R.badInk : R.muted }}>{s.handling.length ? s.handling.join(' · ') : 'Standard handling'}</Text>
                </View>
              </Pressable>
            );
          })}
        </Glass>
      </Rise>
      <Muted style={{ fontSize: 12 }}>Tap each package as you check its label. Pickup can't be confirmed until the count matches the kitchen's.</Muted>
    </Screen>
  );
}
