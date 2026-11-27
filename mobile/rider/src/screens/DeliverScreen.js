import React, { useState } from 'react';
import { Alert, Pressable, Text, TextInput, View } from 'react-native';
import { api } from '../shared/api';
import { Btn, Glass, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import { Header, Screen } from './_Screen';
import { R } from '../rt';

const KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'];
const REASONS = [['no_answer', 'Customer not answering'], ['wrong_address', 'Wrong address'], ['damaged', 'Damaged package'], ['refused', 'Refused'], ['other', 'Other']];

export default function DeliverScreen({ navigation, route }) {
  const { stop } = route.params;
  const [otp, setOtp] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [reason, setReason] = useState(null);
  const [note, setNote] = useState('');

  const press = (k) => { setError(''); if (k === '⌫') setOtp((o) => o.slice(0, -1)); else if (k && otp.length < 4) setOtp((o) => o + k); };
  const deliver = async () => {
    try { setBusy(true); await api(`/rider/stops/${stop.id}/deliver`, { method: 'POST', body: { otp } }); Alert.alert('Delivered ✓', `${stop.order_number} marked delivered.`); navigation.goBack(); }
    catch (e) { setError(e.message); setOtp(''); } finally { setBusy(false); }
  };
  const report = async () => {
    try { setBusy(true); await api(`/rider/stops/${stop.id}/problem`, { method: 'POST', body: { reason, note: note || null } }); Alert.alert('Reported', 'Operations have been told. Keep the package with you until they reply.'); navigation.goBack(); }
    catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  return (
    <Screen footer={<Btn accent={R.green} title={busy ? 'Checking…' : 'Confirm delivery'} onPress={deliver} disabled={busy || otp.length < 4} />}>
      <Header kicker={`${stop.order_number} · ${(stop.customer || '').toUpperCase()}`} title="Confirm delivery" onBack={() => navigation.goBack()} />
      <Text style={{ fontSize: 14, color: R.muted }}>Ask the customer for the 4-digit code in their app.</Text>
      <Rise index={0} style={{ flexDirection: 'row', gap: 12, justifyContent: 'center' }}>
        {[0, 1, 2, 3].map((i) => (
          <View key={i} style={{ width: 62, height: 66, borderRadius: 16, backgroundColor: 'rgba(255,255,255,0.85)', borderWidth: 2, borderColor: error ? R.badInk : i === otp.length ? R.blue : 'rgba(19,50,79,0.08)', alignItems: 'center', justifyContent: 'center' }}>
            <Text style={{ fontSize: 28, fontWeight: '800', color: R.ink }}>{otp[i] || ''}</Text>
          </View>
        ))}
      </Rise>
      {error ? <Text accessibilityLiveRegion="assertive" style={{ color: R.badInk, fontWeight: '700', textAlign: 'center' }}>{error}</Text> : null}
      <Rise index={1} style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'center' }}>
        {KEYS.map((k, i) => (
          <Pressable key={i} onPress={() => press(k)} disabled={!k} accessibilityRole="button" accessibilityLabel={k === '⌫' ? 'Delete' : k} style={{ width: '30%' }}>
            {k ? <Glass style={{ height: 58, alignItems: 'center', justifyContent: 'center', borderRadius: 18 }}>{k === '⌫' ? <Icon name="backspace" size={24} color={R.ink} /> : <Text style={{ fontSize: 24, fontWeight: '800', color: R.ink }}>{k}</Text>}</Glass> : <View style={{ height: 58 }} />}
          </Pressable>
        ))}
      </Rise>
      <Rise index={2}>
        <Glass style={{ borderRadius: 22, padding: 14, gap: 10 }}>
          <Text style={{ fontSize: 15, fontWeight: '800', color: R.ink }}>Problem with this delivery?</Text>
          <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>
            {REASONS.map(([k, l]) => <Pressable key={k} onPress={() => setReason(reason === k ? null : k)} accessibilityRole="radio" accessibilityState={{ selected: reason === k }} style={{ paddingHorizontal: 12, height: 36, borderRadius: 999, justifyContent: 'center', backgroundColor: reason === k ? R.badInk : 'rgba(255,255,255,0.9)', borderWidth: 1, borderColor: 'rgba(19,50,79,0.12)' }}><Text style={{ fontWeight: '800', fontSize: 13, color: reason === k ? '#fff' : R.ink }}>{l}</Text></Pressable>)}
          </View>
          {reason ? (
            <>
              <TextInput value={note} onChangeText={setNote} placeholder="Note for operations (optional)" placeholderTextColor={R.muted} style={{ minHeight: 44, borderRadius: 12, backgroundColor: '#fff', paddingHorizontal: 12, color: R.ink }} />
              <Btn accent={R.badInk} title={busy ? 'Sending…' : 'Report problem'} onPress={report} disabled={busy} />
            </>
          ) : null}
          <Text style={{ fontSize: 12, color: R.muted }}>Choosing one alerts operations with your location and time. No photos of the customer.</Text>
        </Glass>
      </Rise>
    </Screen>
  );
}
