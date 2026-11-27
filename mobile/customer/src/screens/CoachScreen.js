import React, { useCallback, useState } from 'react';
import { Alert, Pressable, ScrollView, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { Btn, Glass, H2, Muted, Rise, Tag } from '../shared/ui';
import { C } from '../shared/theme';
import { rupees } from '../shared/api';
import { Header, Screen } from './_Screen';
import { useAuth } from '../auth';
import { useCart } from '../cart';
import { coachOn, getCoach, syncWellness } from '../wellness';

const TONE = { warn: ['#FFF4E5', '#F2711C'], good: ['#E6F6EC', '#1F8A4C'], info: ['#EAF2FF', '#2F80ED'] };

function Picks({ title, note, items, color, navigation }) {
  if (!items?.length) return null;
  return (
    <View style={{ gap: 8 }}>
      <Text style={{ fontWeight: '800', color: C.ink }}>{title}</Text>
      {note ? <Muted style={{ fontSize: 12, marginTop: -6 }}>{note}</Muted> : null}
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 10 }}>
        {items.map((f) => (
          <Pressable key={f.daily_menu_id} onPress={() => navigation.navigate('Dish', { slug: f.slug })} accessibilityRole="button"
            style={{ width: 200, padding: 12, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.92)', borderWidth: 1.5, borderColor: color + '55', gap: 4 }}>
            <Text style={{ fontWeight: '800', color: C.ink }} numberOfLines={1}>{f.name}</Text>
            <Text style={{ fontSize: 12, color: C.muted }} numberOfLines={2}>{f.why}</Text>
            <Text style={{ fontSize: 12, fontWeight: '800', color }}>{[f.kcal && `${f.kcal} kcal`, rupees(f.price_paise)].filter(Boolean).join(' · ')}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

export default function CoachScreen({ navigation }) {
  const { user, refresh } = useAuth();
  const cart = useCart();
  const [data, setData] = useState(null);
  const [busy, setBusy] = useState(false);
  const on = !!user?.coach_on;

  const load = useCallback(async () => {
    if (!user?.coach_on) return;
    setBusy(true);
    try { await syncWellness(user, true); setData(await getCoach(cart.kitchenId)); } catch (e) { /* keep last */ } finally { setBusy(false); }
  }, [user?.coach_on, cart.kitchenId]);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  const turnOn = async () => { setBusy(true); try { await coachOn(true); await refresh(); } catch (e) { Alert.alert('Could not switch on', 'Check your internet connection and try again.'); } finally { setBusy(false); } };
  const turnOff = () => Alert.alert('Turn off the coach?', 'We will delete the water and workout days we stored for the coach. Your logs stay on this phone.', [
    { text: 'Cancel', style: 'cancel' },
    { text: 'Turn off & delete', style: 'destructive', onPress: async () => { await coachOn(false).catch(() => {}); setData(null); await refresh(); } },
  ]);

  if (!on) {
    return (
      <Screen>
        <Header title="Wellness coach" kicker="WATER · WORKOUT · FOOD" onBack={() => navigation.goBack()} />
        <Rise index={0}>
          <Glass style={{ borderRadius: 30, padding: 22, gap: 12 }}>
            <Text style={{ fontSize: 44 }}>🤖💧🏃🍛</Text>
            <Text style={{ fontSize: 24, fontWeight: '800', color: C.ink }}>Your personal wellness coach</Text>
            <Muted>The coach learns how much water you drink and how often you work out, then helps every day:</Muted>
            {['Tells you when you are behind on water, and how many glasses to drink now', 'Motivates you to keep your daily workout streak', 'Picks dishes from today’s menu for before and after your workout', 'Suggests hydrating dishes on days you drink less'].map((t) => (
              <View key={t} style={{ flexDirection: 'row', gap: 10 }}><Text style={{ color: '#1F8A4C', fontWeight: '800' }}>✓</Text><Text style={{ flex: 1, color: C.ink, lineHeight: 20 }}>{t}</Text></View>
            ))}
            <Btn title={busy ? 'Switching on…' : 'Switch on my coach'} onPress={turnOn} disabled={busy} accent="#1F8A4C" style={{ marginTop: 6 }} />
            <Muted style={{ fontSize: 12 }}>We use only your daily glass count and workout minutes (type, minutes, effort). No health conditions, name or phone number go to the AI. Turn it off any time and we delete the data. Tips are not medical advice.</Muted>
          </Glass>
        </Rise>
      </Screen>
    );
  }

  const w = data?.water, k = data?.workout, f = data?.foods;
  return (
    <Screen refreshing={busy} onRefresh={load}>
      <Header title="Wellness coach" kicker="WATER · WORKOUT · FOOD" onBack={() => navigation.goBack()} />
      {data ? (
        <>
          <Rise index={0}>
            <Glass style={{ borderRadius: 28, padding: 18, gap: 8, backgroundColor: 'rgba(31,138,76,0.08)' }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}><Text style={{ fontSize: 22 }}>🤖</Text><Text style={{ fontWeight: '800', color: C.ink, flex: 1 }}>Today from your coach</Text>{data.ai ? <Tag label="AI" bg="#E6F6EC" color="#1F8A4C" /> : null}</View>
              <Text style={{ fontSize: 16, lineHeight: 23, color: C.ink }}>{data.message}</Text>
            </Glass>
          </Rise>

          <Rise index={1} style={{ flexDirection: 'row', gap: 10 }}>
            <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('Water')}><Glass style={{ padding: 14, borderRadius: 22, gap: 2 }}><Text style={{ fontSize: 20 }}>💧</Text><Text style={{ fontWeight: '800', fontSize: 20, color: C.ink }}>{w.glasses}/{w.goal}</Text><Muted style={{ fontSize: 12 }}>glasses today{w.avg7 != null ? ` · avg ${w.avg7}` : ''}</Muted></Glass></Pressable>
            <Pressable style={{ flex: 1 }} onPress={() => navigation.navigate('Workout')}><Glass style={{ padding: 14, borderRadius: 22, gap: 2 }}><Text style={{ fontSize: 20 }}>🔥</Text><Text style={{ fontWeight: '800', fontSize: 20, color: C.ink }}>{k.minutes}/{k.goal}</Text><Muted style={{ fontSize: 12 }}>min today · streak {k.streak}</Muted></Glass></Pressable>
          </Rise>

          {data.insights.map((it, i) => (
            <Rise key={i} index={2 + i}>
              <View style={{ borderRadius: 22, padding: 14, backgroundColor: (TONE[it.tone] || TONE.info)[0], borderLeftWidth: 5, borderLeftColor: (TONE[it.tone] || TONE.info)[1], gap: 4 }}>
                <Text style={{ fontWeight: '800', color: C.ink }}>{it.icon} {it.title}</Text>
                <Text style={{ color: C.ink, lineHeight: 20 }}>{it.text}</Text>
              </View>
            </Rise>
          ))}

          <Rise index={6}>
            <Glass style={{ borderRadius: 26, padding: 16, gap: 14 }}>
              <H2>🍛 Picked for you{f?.date ? ` · menu of ${new Date(f.date).toLocaleDateString('en-IN', { weekday: 'long' })}` : ''}</H2>
              {f && (f.after.length || f.before.length || f.hydrate.length) ? (
                <>
                  {k.minutes > 0 ? <Picks title="After your workout" note="Protein for recovery" items={f.after} color="#F2711C" navigation={navigation} /> : <Picks title="Before your workout" note="Light energy, 1–2 hours before" items={f.before} color="#F4B400" navigation={navigation} />}
                  <Picks title="Hydrating dishes" note="They add water to your day" items={f.hydrate} color="#2F80ED" navigation={navigation} />
                  <Picks title="Saltier today — drink an extra glass with these" items={f.salty} color="#E0433B" navigation={navigation} />
                  {k.minutes > 0 ? null : <Picks title="After your workout" note="Protein for recovery" items={f.after} color="#F2711C" navigation={navigation} />}
                </>
              ) : <Muted>{cart.kitchenId ? 'Picks appear once the next menu is published.' : 'Choose your delivery address on the Today screen to get picks from your kitchen’s menu.'}</Muted>}
            </Glass>
          </Rise>

          <View style={{ flexDirection: 'row', gap: 10 }}>
            <Btn title="💧 Water" kind="ghost" onPress={() => navigation.navigate('Water')} style={{ flex: 1 }} />
            <Btn title="🏃 Workout" kind="ghost" onPress={() => navigation.navigate('Workout')} style={{ flex: 1 }} />
          </View>
          <Muted style={{ fontSize: 12, textAlign: 'center' }}>{data.disclaimer}</Muted>
          <Pressable onPress={turnOff} style={{ alignSelf: 'center', padding: 8 }}><Text style={{ color: C.badInk, fontWeight: '800' }}>Turn off the coach and delete its data</Text></Pressable>
        </>
      ) : <Muted>{busy ? 'Your coach is looking at your week…' : 'Pull down to refresh.'}</Muted>}
    </Screen>
  );
}
