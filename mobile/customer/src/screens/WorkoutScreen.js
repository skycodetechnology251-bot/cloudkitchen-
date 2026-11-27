import React, { useCallback, useEffect, useState } from 'react';
import { Alert, DeviceEventEmitter, Platform, Pressable, ScrollView, Text, View } from 'react-native';
import Svg, { Circle, Defs, LinearGradient, Stop } from 'react-native-svg';
import { useFocusEffect } from '@react-navigation/native';
import { Btn, Glass, H2, Muted, Rise, Segmented, Toggle } from '../shared/ui';
import { C } from '../shared/theme';
import { rupees } from '../shared/api';
import { Header, Screen } from './_Screen';
import { useAuth } from '../auth';
import { useCart } from '../cart';
import { todayKey, fmtTime } from '../water';
import { addWorkout, DEFAULTS, getLog, getSettings, minutesOn, removeLast, ROUTINES, saveSettings, streak, TYPES, typeIcon, typeLabel, WORKOUT_EVENT } from '../workout';
import { getWorkoutFoods, syncWellness } from '../wellness';

const ORANGE = '#F2711C';
const SUN = '#F4B400';

function Ring({ value, goal, size = 180 }) {
  const stroke = 16, r = (size - stroke) / 2, c = 2 * Math.PI * r;
  const p = Math.max(0, Math.min(1, goal ? value / goal : 0));
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }} accessibilityLabel={`${value} of ${goal} active minutes today`}>
      <Svg width={size} height={size} style={{ position: 'absolute', transform: [{ rotate: '-90deg' }] }}>
        <Defs><LinearGradient id="k" x1="0" y1="0" x2="1" y2="1"><Stop offset="0" stopColor={SUN} /><Stop offset="1" stopColor={ORANGE} /></LinearGradient></Defs>
        <Circle cx={size / 2} cy={size / 2} r={r} stroke="rgba(242,113,28,0.12)" strokeWidth={stroke} fill="none" />
        <Circle cx={size / 2} cy={size / 2} r={r} stroke="url(#k)" strokeWidth={stroke} fill="none" strokeLinecap="round" strokeDasharray={`${c} ${c}`} strokeDashoffset={c * (1 - p)} />
      </Svg>
      <Text style={{ fontSize: 34 }}>🏃</Text>
      <Text style={{ fontSize: 32, fontWeight: '800', color: C.ink }}>{value}<Text style={{ fontSize: 17, color: C.muted }}> / {goal} min</Text></Text>
      <Text style={{ fontSize: 12, fontWeight: '800', color: ORANGE }}>{p >= 1 ? 'GOAL DONE 🎉' : 'ACTIVE TODAY'}</Text>
    </View>
  );
}

function FoodRow({ title, note, items, color, onOpen }) {
  if (!items?.length) return null;
  return (
    <View style={{ gap: 8 }}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}><View style={{ width: 10, height: 10, borderRadius: 3, backgroundColor: color }} /><Text style={{ fontWeight: '800', color: C.ink }}>{title}</Text></View>
      <Muted style={{ fontSize: 12, marginTop: -4 }}>{note}</Muted>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 10 }}>
        {items.map((f) => (
          <Pressable key={f.daily_menu_id} onPress={() => onOpen(f)} accessibilityRole="button" style={{ width: 210, padding: 12, borderRadius: 18, backgroundColor: 'rgba(255,255,255,0.9)', borderWidth: 1.5, borderColor: color + '55', gap: 4 }}>
            <Text style={{ fontWeight: '800', color: C.ink }} numberOfLines={1}>{f.name}</Text>
            <Text style={{ fontSize: 12, color: C.muted }} numberOfLines={2}>{f.why}</Text>
            <Text style={{ fontSize: 12, fontWeight: '800', color }}>{[f.kcal && `${f.kcal} kcal`, f.protein_g && `${f.protein_g} g protein`, rupees(f.price_paise)].filter(Boolean).join(' · ')}</Text>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  );
}

export default function WorkoutScreen({ navigation }) {
  const { user } = useAuth();
  const cart = useCart();
  const [s, setS] = useState(DEFAULTS);
  const [log, setLog] = useState({});
  const [type, setType] = useState('walk');
  const [mins, setMins] = useState(20);
  const [effort, setEffort] = useState('moderate');
  const [foods, setFoods] = useState(null);

  const load = useCallback(async () => {
    const st = await getSettings(); setS(st); setType(st.fav); setLog(await getLog());
    if (cart.kitchenId) getWorkoutFoods(cart.kitchenId).then(setFoods).catch(() => {});
  }, [cart.kitchenId]);
  useFocusEffect(useCallback(() => { load(); }, [load]));
  useEffect(() => { const sub = DeviceEventEmitter.addListener(WORKOUT_EVENT, async () => setLog(await getLog())); return () => sub.remove(); }, []);

  const today = minutesOn(log, todayKey());
  const st = streak(log);
  const routine = ROUTINES[new Date().getDay()];
  const days = Array.from({ length: 7 }, (_, i) => { const d = new Date(); d.setDate(d.getDate() - (6 - i)); return d; });
  const week = days.reduce((n, d) => n + minutesOn(log, todayKey(d)), 0);

  const logIt = async (entry) => {
    const next = await addWorkout(entry); setLog(next);
    syncWellness(user, true);
    const total = minutesOn(next, todayKey());
    if (total >= s.goal && total - entry.minutes < s.goal) Alert.alert('Goal done! 🎉', `${total} active minutes today. Drink a glass or two of water and refuel with some protein.`);
  };
  const update = async (patch) => {
    const next = { ...s, ...patch }; setS(next);
    const ok = await saveSettings(next).catch(() => false);
    if (!ok && next.enabled) { setS({ ...next, enabled: false }); await saveSettings({ ...next, enabled: false }); Alert.alert('Notifications are off', 'Allow notifications for this app in your phone settings to get workout reminders.'); }
  };
  const openDish = (f) => navigation.navigate('Dish', { slug: f.slug });

  return (
    <Screen>
      <Header title="Daily workout" kicker="MOVE A LITTLE EVERY DAY" onBack={() => navigation.goBack()} />

      <Rise index={0}>
        <Glass style={{ borderRadius: 30, padding: 20, alignItems: 'center', gap: 12 }}>
          <Ring value={today} goal={s.goal} />
          <View style={{ flexDirection: 'row', gap: 10, alignSelf: 'stretch' }}>
            <View style={{ flex: 1, padding: 12, borderRadius: 18, backgroundColor: 'rgba(242,113,28,0.1)', alignItems: 'center' }}><Text style={{ fontSize: 22, fontWeight: '800', color: C.ink }}>🔥 {st}</Text><Muted style={{ fontSize: 12 }}>day streak</Muted></View>
            <View style={{ flex: 1, padding: 12, borderRadius: 18, backgroundColor: 'rgba(244,180,0,0.14)', alignItems: 'center' }}><Text style={{ fontSize: 22, fontWeight: '800', color: C.ink }}>{week}</Text><Muted style={{ fontSize: 12 }}>min this week</Muted></View>
          </View>
          <Muted style={{ textAlign: 'center' }}>{st >= 3 ? `${st} days in a row — amazing, keep it up!` : today >= s.goal ? 'Goal done for today. See you tomorrow!' : today > 0 ? `${s.goal - today} more minutes to reach today's goal.` : 'Start small: even 10 minutes today counts.'}</Muted>
        </Glass>
      </Rise>

      <Rise index={1}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 12 }}>
          <H2>Log a workout</H2>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>
            {TYPES.map(([k, l, i]) => (
              <Pressable key={k} onPress={() => setType(k)} accessibilityRole="radio" accessibilityState={{ selected: type === k }}
                style={{ paddingHorizontal: 12, height: 38, borderRadius: 999, flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: type === k ? ORANGE : 'rgba(255,255,255,0.8)', borderWidth: type === k ? 0 : 1, borderColor: 'rgba(18,48,42,0.12)' }}>
                <Text>{i}</Text><Text style={{ fontWeight: '800', fontSize: 13, color: type === k ? '#fff' : C.ink }}>{l}</Text>
              </Pressable>
            ))}
          </View>
          <Segmented small value={mins} onChange={setMins} options={[[10, '10 min'], [20, '20'], [30, '30'], [45, '45'], [60, '60']]} />
          <Segmented small value={effort} onChange={setEffort} options={[['easy', 'Easy'], ['moderate', 'Moderate'], ['hard', 'Hard']]} />
          <Btn title={`${typeIcon(type)} Log ${mins} min ${typeLabel(type).toLowerCase()}`} accent={ORANGE} onPress={() => logIt({ type, minutes: mins, intensity: effort })} />
          {(log[todayKey()] || []).length ? (
            <View style={{ gap: 4 }}>
              {(log[todayKey()] || []).map((w, i) => <Text key={i} style={{ color: C.ink, fontWeight: '600' }}>{typeIcon(w.type)} {typeLabel(w.type)} · {w.minutes} min · {w.intensity}</Text>)}
              <Pressable onPress={async () => setLog(await removeLast())}><Text style={{ color: C.badInk, fontWeight: '800', marginTop: 4 }}>Undo last</Text></Pressable>
            </View>
          ) : null}
        </Glass>
      </Rise>

      <Rise index={2}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 8, borderWidth: 1.5, borderColor: 'rgba(244,180,0,0.6)' }}>
          <Text style={{ fontSize: 12, fontWeight: '800', color: ORANGE, letterSpacing: 0.4 }}>TODAY'S IDEA · {routine.minutes} MIN</Text>
          <H2>{routine.title}</H2>
          {routine.steps.map((x, i) => <Text key={i} style={{ color: C.ink, lineHeight: 21 }}>• {x}</Text>)}
          <Btn small title={`Done it · log ${routine.minutes} min`} accent={ORANGE} onPress={() => logIt({ type: routine.type, minutes: routine.minutes, intensity: 'moderate' })} style={{ marginTop: 6 }} />
        </Glass>
      </Rise>

      <Rise index={3}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 10 }}>
          <H2>Last 7 days</H2>
          <View style={{ flexDirection: 'row', alignItems: 'flex-end', gap: 8, height: 110 }}>
            {days.map((d) => {
              const n = minutesOn(log, todayKey(d)); const h = Math.max(4, Math.min(1, n / Math.max(1, s.goal)) * 84); const isToday = todayKey(d) === todayKey();
              return (
                <View key={todayKey(d)} style={{ flex: 1, alignItems: 'center', gap: 4 }}>
                  <Text style={{ fontSize: 11, fontWeight: '800', color: C.muted }}>{n || ''}</Text>
                  <View style={{ width: '70%', height: h, borderRadius: 8, backgroundColor: n >= s.goal ? ORANGE : isToday ? SUN : 'rgba(242,113,28,0.3)' }} />
                  <Text style={{ fontSize: 11, fontWeight: isToday ? '800' : '600', color: isToday ? C.ink : C.muted }}>{isToday ? 'Today' : d.toLocaleDateString('en-IN', { weekday: 'short' }).slice(0, 3)}</Text>
                </View>
              );
            })}
          </View>
        </Glass>
      </Rise>

      <Rise index={4}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 14 }}>
          <H2>🍛 Eat for your workout</H2>
          {foods && (foods.before?.length || foods.after?.length) ? (
            <>
              <FoodRow title="Before you work out" note="Light energy, 1–2 hours before" items={foods.before} color={SUN} onOpen={openDish} />
              <FoodRow title="After your workout" note="Protein helps your muscles recover" items={foods.after} color={ORANGE} onOpen={openDish} />
              <FoodRow title="Hydrating dishes" note="They add water to your day" items={foods.hydrate} color="#2F80ED" onOpen={openDish} />
            </>
          ) : (
            <Muted>{cart.kitchenId ? 'Picks from the menu appear here once the next menu is published.' : 'Choose your delivery address on the Today screen to see picks from your kitchen’s menu.'}</Muted>
          )}
          <Muted style={{ fontSize: 12 }}>Everyday tips: before — rice, millets, fruit; after — dal, sundal, egg, curd or paneer; and drink 1–2 extra glasses of water.</Muted>
        </Glass>
      </Rise>

      <Rise index={5}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 12 }}>
          <Toggle value={s.enabled} onChange={(v) => update({ enabled: v })} label="Daily workout reminder" note={s.enabled ? `Every day at ${fmtTime(s.hour * 60 + s.minute)} with that day's idea` : 'Off'} />
          <View style={{ gap: 8, opacity: s.enabled ? 1 : 0.5 }}>
            <Text style={{ fontWeight: '800', color: C.ink }}>Remind me at</Text>
            <Segmented small value={s.hour * 60 + s.minute} onChange={(v) => update({ hour: Math.floor(v / 60), minute: v % 60 })} options={[[360, '6 AM'], [420, '7 AM'], [1020, '5 PM'], [1110, '6:30 PM'], [1200, '8 PM']]} />
            <Text style={{ fontWeight: '800', color: C.ink, marginTop: 6 }}>Daily goal</Text>
            <Segmented small value={s.goal} onChange={(v) => update({ goal: v })} options={[[15, '15 min'], [20, '20'], [30, '30'], [45, '45'], [60, '60']]} />
            <Text style={{ fontWeight: '800', color: C.ink, marginTop: 6 }}>“Did 20 min” button logs a</Text>
            <Segmented small value={s.fav} onChange={(v) => update({ fav: v })} options={[['walk', 'Walk'], ['yoga', 'Yoga'], ['home', 'Home'], ['gym', 'Gym'], ['run', 'Run']]} />
          </View>
          <Muted style={{ fontSize: 12 }}>{Platform.OS === 'ios' ? 'The reminder and its buttons also show on your Apple Watch.' : 'The reminder and its buttons also show on your Wear OS watch.'}</Muted>
        </Glass>
      </Rise>

      <Muted style={{ fontSize: 12, textAlign: 'center', paddingHorizontal: 8 }}>Start slowly and stop if you feel pain, dizziness or breathlessness. If you have a health condition or are pregnant, check with your doctor before starting a new workout.</Muted>
    </Screen>
  );
}
