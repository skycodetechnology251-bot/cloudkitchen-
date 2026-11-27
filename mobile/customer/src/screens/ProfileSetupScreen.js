import React, { useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { api } from '../shared/api';
import { Btn, Field, Muted, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';
import { Header, Screen, Steps } from './_Screen';
import { useAuth } from '../auth';

export default function ProfileSetupScreen({ navigation }) {
  const { user, signOut } = useAuth();
  const [name, setName] = useState(user?.name === 'New customer' ? '' : user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [dob, setDob] = useState('');
  const [forWho, setForWho] = useState(user?.ordering_for || 'self');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  const fmtDob = (t) => { const d = t.replace(/\D/g, '').slice(0, 8); return [d.slice(0, 2), d.slice(2, 4), d.slice(4)].filter(Boolean).join(' / '); };
  const next = async () => {
    if (name.trim().length < 2) { setError('Please enter your name.'); return; }
    try {
      setBusy(true);
      const body = { name: name.trim(), email: email.trim() || null, ordering_for: forWho };
      const d = dob.replace(/\D/g, '');
      if (d.length === 8) body.date_of_birth = `${d.slice(4)}-${d.slice(2, 4)}-${d.slice(0, 2)}`;
      const u = await api('/me', { method: 'PATCH', body });
      navigation.navigate('Health', { onboarding: true, user: u });
    } catch (e) { setError(e.message); } finally { setBusy(false); }
  };
  const phone = user?.phone ? '+91 ' + user.phone.slice(0, 5) + ' ' + user.phone.slice(5) : '';

  return (
    <Screen footer={<Btn title={busy ? 'Saving…' : 'Continue'} onPress={next} disabled={busy} />}>
      <Header onBack={() => signOut()} right={<Text style={{ fontSize: 13, fontWeight: '700', color: C.muted }}>Step 1 of 2</Text>} />
      <Steps step={1} />
      <Rise index={0}><Text style={{ fontSize: 28, fontWeight: '800', color: C.ink }}>Create your account</Text><Muted style={{ fontSize: 15 }}>Just your name to get started.</Muted></Rise>
      <Rise index={1}><Field label="Full name *" value={name} onChangeText={(t) => { setName(t); setError(''); }} autoComplete="name" textContentType="name" error={error} /></Rise>
      <Rise index={2} style={{ gap: 6 }}>
        <Text style={{ fontSize: 13, fontWeight: '800', color: '#27403A' }}>Mobile number</Text>
        <View style={{ minHeight: 50, borderRadius: 14, backgroundColor: 'rgba(227,241,236,0.9)', paddingHorizontal: 14, flexDirection: 'row', alignItems: 'center' }}>
          <Text style={{ flex: 1, fontSize: 16, fontWeight: '700', color: C.ink }}>{phone}</Text>
          <Icon name="check" size={16} color={C.teal} /><Text style={{ color: C.teal, fontWeight: '800', fontSize: 13, marginLeft: 4 }}>Verified</Text>
        </View>
      </Rise>
      <Rise index={3}><Field label="Email (optional, for receipts)" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" autoComplete="email" placeholder="you@example.com" /></Rise>
      <Rise index={4}><Field label="Date of birth (optional)" value={dob} onChangeText={(t) => setDob(fmtDob(t))} placeholder="DD / MM / YYYY" keyboardType="number-pad" /></Rise>
      <Rise index={5} style={{ gap: 8 }}>
        <Text style={{ fontSize: 13, fontWeight: '800', color: '#27403A' }}>I'm ordering for</Text>
        <View style={{ flexDirection: 'row', gap: 10 }} accessibilityRole="radiogroup">
          {[['self', 'Myself'], ['family', 'My family']].map(([k, l]) => (
            <Pressable key={k} accessibilityRole="radio" accessibilityState={{ selected: forWho === k }} onPress={() => setForWho(k)}
              style={{ flex: 1, minHeight: 50, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: forWho === k ? 2 : 1, borderColor: forWho === k ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: forWho === k ? 'rgba(227,241,236,0.95)' : 'rgba(255,255,255,0.75)' }}>
              <Text style={{ fontWeight: '800', fontSize: 15, color: C.ink }}>{l}</Text>
            </Pressable>
          ))}
        </View>
      </Rise>
      <Muted style={{ fontSize: 12 }}>You'll add your delivery address when you place your first order.</Muted>
    </Screen>
  );
}
