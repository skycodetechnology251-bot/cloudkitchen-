import React, { useEffect, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { api } from '../shared/api';
import { Glass, Muted, Rise, Toggle } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useAuth } from '../auth';

const TOPICS = [
  ['order', 'Order updates', 'Always on while an order is active'],
  ['reminder', '10 AM lunch reminder', 'One question a day'],
  ['wallet', 'Wallet alerts', 'When your balance runs low'],
  ['plan', 'Diet plan updates', 'From your dietitian'],
  ['offers', 'Offers & news', 'Marketing · off until you say yes'],
  ['weekly', 'Weekly summary', 'Your week of meals'],
];
const CH = [['push', 'PUSH', 'P', '#0F5C4D'], ['whatsapp', 'WA', 'W', '#1E8A5A'], ['sms', 'SMS', 'S', '#1F5FA8'], ['email', 'MAIL', 'E', '#8A5A12']];

export default function NotificationsScreen({ navigation }) {
  const { user } = useAuth();
  const [p, setP] = useState(null);
  useEffect(() => { api('/me/notifications').then(setP).catch(() => {}); }, []);
  const save = async (patch) => {
    const before = p; const next = JSON.parse(JSON.stringify(p));
    Object.entries(patch).forEach(([t, chs]) => Object.assign(next[t], chs)); setP(next);
    try { setP(await api('/me/notifications', { method: 'PUT', body: { prefs: patch } })); } catch (e) { setP(before); }
  };
  const channelOn = (c) => p && TOPICS.some(([t]) => p[t]?.[c]);
  const setChannel = (c, v) => save(Object.fromEntries(TOPICS.filter(([t]) => !(t === 'offers' && v)).map(([t]) => [t, { [c]: v }])));
  const masked = user?.phone ? `+91 ${user.phone.slice(0, 2)}••• ••${user.phone.slice(-3)}` : '';
  const sub = { push: 'On this phone', whatsapp: `${masked} · verified`, sms: 'Log-in codes always come by SMS', email: user?.email || 'Add an email in Profile' };
  const names = { push: 'Push notifications', whatsapp: 'WhatsApp', sms: 'SMS', email: 'Email' };

  return (
    <Screen>
      <Header title="Notifications & messages" onBack={() => navigation.goBack()} />
      {p ? (
        <>
          <Rise index={0}>
            <Glass style={{ borderRadius: 26, padding: 16, gap: 4 }}>
              <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink, marginBottom: 6 }}>How we reach you</Text>
              {CH.map(([c, , letter, color]) => {
                const disabled = c === 'email' && !user?.email;
                return (
                  <View key={c} style={{ flexDirection: 'row', alignItems: 'center', gap: 12, opacity: disabled ? 0.55 : 1 }}>
                    <View style={{ width: 36, height: 36, borderRadius: 10, backgroundColor: color, alignItems: 'center', justifyContent: 'center' }}><Text style={{ color: '#fff', fontWeight: '800' }}>{letter}</Text></View>
                    <View style={{ flex: 1 }}><Toggle value={channelOn(c) && !disabled} onChange={(v) => !disabled && setChannel(c, v)} label={names[c]} note={sub[c]} /></View>
                  </View>
                );
              })}
            </Glass>
          </Rise>
          <Rise index={1}>
            <Glass style={{ borderRadius: 26, padding: 16 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 8 }}>
                <Text style={{ flex: 1, fontSize: 17, fontWeight: '800', color: C.ink }}>What we send</Text>
                <Text style={{ fontSize: 11, fontWeight: '800', color: C.muted, letterSpacing: 0.6 }}>PUSH · WA · SMS · MAIL</Text>
              </View>
              {TOPICS.map(([t, l, n]) => (
                <View key={t} style={{ flexDirection: 'row', alignItems: 'center', gap: 8, paddingVertical: 10, borderTopWidth: 1, borderTopColor: 'rgba(18,48,42,0.06)' }}>
                  <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{l}</Text><Text style={{ fontSize: 12, color: C.muted }}>{n}</Text></View>
                  {CH.map(([c, short]) => {
                    const on = !!p[t]?.[c]; const locked = t === 'order' && c === 'push';
                    const disabled = locked || (c === 'email' && !user?.email);
                    return (
                      <Pressable key={c} disabled={disabled} onPress={() => save({ [t]: { [c]: !on } })} accessibilityRole="checkbox" accessibilityState={{ checked: on, disabled }} accessibilityLabel={`${l} by ${names[c]}`}
                        style={{ width: 34, height: 34, borderRadius: 10, alignItems: 'center', justifyContent: 'center', backgroundColor: on ? C.teal : 'rgba(255,255,255,0.9)', borderWidth: on ? 0 : 1.5, borderColor: 'rgba(18,48,42,0.15)', opacity: disabled && !on ? 0.4 : 1 }}>
                        {on ? <Icon name="check" size={16} color="#fff" width={3} /> : null}
                      </Pressable>
                    );
                  })}
                </View>
              ))}
              <Muted style={{ fontSize: 12, marginTop: 6 }}>Health details are never included in any message. Marketing is off until you turn it on.</Muted>
            </Glass>
          </Rise>
        </>
      ) : <Muted>Loading…</Muted>}
    </Screen>
  );
}
