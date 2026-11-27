import React, { useEffect, useRef, useState } from 'react';
import { Platform, Pressable, Text, TextInput, View } from 'react-native';
import { api } from '../shared/api';
import { Btn, Glass, Muted, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useAuth } from '../auth';

/** Error card styled like the design's "other states". */
function Notice({ icon, bold, text, tone = 'bad' }) {
  const [bg, fg] = tone === 'bad' ? [C.bad, C.badInk] : ['rgba(255,255,255,0.7)', C.ink];
  return <View accessibilityLiveRegion="assertive" style={{ flexDirection: 'row', gap: 10, alignItems: 'flex-start', backgroundColor: bg, borderRadius: 16, padding: 12 }}><Icon name={icon} size={18} color={fg} /><Text style={{ flex: 1, color: fg, fontSize: 13, lineHeight: 18 }}><Text style={{ fontWeight: '800' }}>{bold} </Text>{text}</Text></View>;
}

export default function OtpScreen({ navigation, route }) {
  const { phone, googleLink } = route.params;
  const { signIn } = useAuth();
  const [code, setCode] = useState('');
  const [error, setError] = useState(null);
  const [wait, setWait] = useState(30);
  const [busy, setBusy] = useState(false);
  const input = useRef(null);

  useEffect(() => { const t = setInterval(() => setWait((w) => Math.max(0, w - 1)), 1000); return () => clearInterval(t); }, []);
  useEffect(() => { if (code.length === 6) verify(); }, [code]);

  const verify = async () => {
    try {
      setBusy(true); setError(null);
      const r = await api('/auth/otp/verify', { method: 'POST', body: { phone, code, device: Platform.OS, google_link_token: googleLink } });
      await signIn(r);
    } catch (e) {
      const m = e.message || '';
      setError(e.status === 429 || /too many/i.test(m) ? ['lock', 'Too many tries.', 'Please wait 10 minutes before requesting a new code.']
        : /expired/i.test(m) ? ['info', 'Code expired.', 'Tap Resend to get a new one.']
        : ['info', 'Wrong code.', m.replace(/^The code is wrong\.?\s*/i, '') || 'Check the SMS and try again.']);
      setCode('');
    } finally { setBusy(false); }
  };
  const resend = async () => { try { await api('/auth/otp/request', { method: 'POST', body: { phone } }); setWait(30); setError(null); } catch (e) { setError(['info', 'Could not resend.', e.message]); } };
  const pretty = '+91 ' + phone.slice(0, 5) + ' ' + phone.slice(5);

  return (
    <Screen bottom={40}>
      <Header onBack={() => navigation.goBack()} />
      <Rise index={0}><Glass style={{ width: 54, height: 54, borderRadius: 16, alignItems: 'center', justifyContent: 'center' }}><Icon name="mobile" size={24} /></Glass></Rise>
      <Rise index={1} style={{ gap: 6 }}>
        <Text style={{ fontSize: 28, fontWeight: '800', color: C.ink }}>Enter the code</Text>
        <Text style={{ fontSize: 15, color: C.muted, lineHeight: 22 }}>Sent by SMS to <Text style={{ fontWeight: '800', color: C.ink }}>{pretty}</Text> · <Text onPress={() => navigation.goBack()} style={{ fontWeight: '800', color: C.ink, textDecorationLine: 'underline' }}>Change number</Text></Text>
      </Rise>
      <Rise index={2} style={{ gap: 8 }}>
        <Text style={{ fontSize: 13, fontWeight: '800', color: C.ink }}>6-digit code</Text>
        <Pressable onPress={() => input.current?.focus()} accessibilityLabel={`6-digit code, ${code.length} entered`} style={{ flexDirection: 'row', gap: 8 }}>
          {[0, 1, 2, 3, 4, 5].map((i) => {
            const cur = i === code.length;
            return (
              <View key={i} style={{ flex: 1, height: 58, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: cur ? 2 : 1, borderColor: error ? C.badInk : cur ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: cur ? '#FFF8E6' : 'rgba(255,255,255,0.85)' }}>
                <Text style={{ fontSize: 24, fontWeight: '800', color: C.ink }}>{code[i] || ''}</Text>
              </View>
            );
          })}
        </Pressable>
        <TextInput ref={input} value={code} onChangeText={(t) => setCode(t.replace(/\D/g, '').slice(0, 6))} keyboardType="number-pad" autoFocus textContentType="oneTimeCode" autoComplete="sms-otp" style={{ position: 'absolute', opacity: 0, height: 1, width: 1 }} />
      </Rise>
      <Rise index={3} style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
        <Muted>Didn't get it?</Muted>
        {wait > 0 ? <Text style={{ fontWeight: '800', color: C.ink }}>Resend in 0:{String(wait).padStart(2, '0')}</Text> : <Pressable onPress={resend} hitSlop={8}><Text style={{ color: C.teal, fontWeight: '800' }}>Resend code</Text></Pressable>}
      </Rise>
      <Rise index={4}><Btn title={busy ? 'Checking…' : 'Verify & continue'} onPress={verify} disabled={busy || code.length < 6} /></Rise>
      {error ? <Notice icon={error[0]} bold={error[1]} text={error[2]} /> : null}
    </Screen>
  );
}
