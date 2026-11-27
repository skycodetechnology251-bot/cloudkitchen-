import React, { useState } from 'react';
import { Platform, Text, TextInput, View } from 'react-native';
import { api } from '../shared/api';
import { Btn, Field, Glass, Muted, Rise } from '../shared/ui';
import { C } from '../shared/theme';
import { Screen } from './_Screen';
import { useRider } from '../rider';

export default function LoginScreen() {
  const { signIn } = useRider();
  const [phone, setPhone] = useState('');
  const [code, setCode] = useState('');
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const send = async () => {
    if (!/^[6-9]\d{9}$/.test(phone)) { setError('Enter your 10-digit mobile number.'); return; }
    try { setBusy(true); setError(''); await api('/auth/otp/request', { method: 'POST', body: { phone } }); setSent(true); } catch (e) { setError(e.message); } finally { setBusy(false); }
  };
  const verify = async () => {
    try {
      setBusy(true); setError('');
      const r = await api('/auth/otp/verify', { method: 'POST', body: { phone, code, device: 'rider-' + Platform.OS } });
      if (r.user.role !== 'rider') { setError('This number is not registered as a Daily Table delivery partner. Ask your kitchen manager to add you.'); return; }
      await signIn(r);
    } catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  return (
    <Screen>
      <View style={{ height: 40 }} />
      <Rise index={0}>
        <View style={{ width: 72, height: 72, borderRadius: 22, backgroundColor: '#1F5FA8', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 34 }}>🛵</Text></View>
        <Text style={{ fontSize: 30, fontWeight: '800', color: C.navy, marginTop: 16 }}>Daily Table{'\n'}Delivery partner</Text>
        <Muted style={{ fontSize: 15, marginTop: 6 }}>Log in with the mobile number your kitchen registered.</Muted>
      </Rise>
      <Rise index={1}><Glass style={{ padding: 18, gap: 14 }}>
        <Field label="Mobile number" value={phone} onChangeText={(t) => { setPhone(t.replace(/\D/g, '').slice(0, 10)); setSent(false); }} keyboardType="number-pad" placeholder="90000 00001" />
        {sent ? <Field label="6-digit code sent by SMS" value={code} onChangeText={(t) => setCode(t.replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" textContentType="oneTimeCode" autoComplete="sms-otp" autoFocus /> : null}
        {error ? <Text style={{ color: C.badInk, fontWeight: '700' }}>{error}</Text> : null}
        <Btn accent={C.blue} title={busy ? 'Please wait…' : sent ? 'Log in' : 'Send code'} onPress={sent ? verify : send} disabled={busy || (sent && code.length < 6)} />
      </Glass></Rise>
    </Screen>
  );
}
