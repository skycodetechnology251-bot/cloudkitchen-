import React, { useCallback, useEffect, useState } from 'react';
import { Alert, DeviceEventEmitter, Platform, Text, View } from 'react-native';
import Svg, { Circle, Defs, LinearGradient, Stop } from 'react-native-svg';
import { useFocusEffect } from '@react-navigation/native';
import { Btn, Glass, H2, Muted, Rise, Segmented, Toggle } from '../shared/ui';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { addGlass, DEFAULTS, fmtTime, getLog, getSettings, processLastResponse, saveSettings, slots, todayKey, WATER_EVENT } from '../water';

const BLUE = '#2F80ED';
const SKY = '#56CCF2';

function Ring({ value, goal, size = 190 }) {
  const stroke = 16, r = (size - stroke) / 2, c = 2 * Math.PI * r;
  const p = Math.max(0, Math.min(1, goal ? value / goal : 0));
  return (
    <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }} accessibilityLabel={`${value} of ${goal} glasses today`}>
      <Svg width={size} height={size} style={{ position: 'absolute', transform: [{ rotate: '-90deg' }] }}>
        <Defs><LinearGradient id="w" x1="0" y1="0" x2="1" y2="1"><Stop offset="0" stopColor={SKY} /><Stop offset="1" stopColor={BLUE} /></LinearGradient></Defs>
        <Circle cx={size / 2} cy={size / 2} r={r} stroke="rgba(47,128,237,0.12)" strokeWidth={stroke} fill="none" />
        <Circle cx={size / 2} cy={size / 2} r={r} stroke="url(#w)" strokeWidth={stroke} fill="none" strokeLinecap="round" strokeDasharray={`${c} ${c}`} strokeDashoffset={c * (1 - p)} />
      </Svg>
      <Text style={{ fontSize: 40 }}>💧</Text>
      <Text style={{ fontSize: 34, fontWeight: '800', color: C.ink }}>{value}<Text style={{ fontSize: 18, color: C.muted }}> / {goal}</Text></Text>
      <Text style={{ fontSize: 12, fontWeight: '800', color: BLUE }}>{p >= 1 ? 'GOAL REACHED 🎉' : 'GLASSES TODAY'}</Text>
    </View>
  );
}

export default function WaterScreen({ navigation }) {
  const [s, setS] = useState(DEFAULTS);
  const [log, setLog] = useState({});
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    await processLastResponse(); // a tap on the phone or watch while the app was closed is counted now
    setS(await getSettings());
    setLog(await getLog());
  }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));
  useEffect(() => {
    const sub = DeviceEventEmitter.addListener(WATER_EVENT, async () => setLog(await getLog()));
    return () => sub.remove();
  }, []);

  const today = log[todayKey()] || 0;
  const change = async (n) => setLog(await addGlass(n));
  const update = async (patch) => {
    const next = { ...s, ...patch };
    if (next.end <= next.start) next.end = Math.min(23, next.start + 1);
    setS(next); setSaving(true);
    const ok = await saveSettings(next).catch(() => false);
    setSaving(false);
    if (!ok && next.enabled) {
      setS({ ...next, enabled: false }); await saveSettings({ ...next, enabled: false });
      Alert.alert('Notifications are off', 'Allow notifications for this app in your phone settings to get water reminders.');
    }
  };

  const days = Array.from({ length: 7 }, (_, i) => { const d = new Date(); d.setDate(d.getDate() - (6 - i)); return d; });
  const times = slots(s);
  const now = new Date().getHours() * 60 + new Date().getMinutes();
  const next = times.find((t) => t > now);

  return (
    <Screen>
      <Header title="Drink water" kicker="STAY HYDRATED" onBack={() => navigation.goBack()} />

      <Rise index={0}>
        <Glass style={{ borderRadius: 30, padding: 20, alignItems: 'center', gap: 14 }}>
          <Ring value={today} goal={s.goal} />
          <Muted style={{ textAlign: 'center' }}>{today * s.glassMl} ml of {s.goal * s.glassMl} ml · one glass ≈ {s.glassMl} ml</Muted>
          <View style={{ flexDirection: 'row', gap: 10, alignSelf: 'stretch' }}>
            <Btn kind="ghost" title="− Remove" onPress={() => change(-1)} disabled={today === 0} style={{ flex: 1 }} />
            <Btn title="💧 Add a glass" onPress={() => change(1)} accent={BLUE} style={{ flex: 1.6 }} />
          </View>
        </Glass>
      </Rise>

      <Rise index={1}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 10 }}>
          <H2>Last 7 days</H2>
          <View style={{ flexDirection: 'row', alignItems: 'flex-end', gap: 8, height: 110 }}>
            {days.map((d) => {
              const n = log[todayKey(d)] || 0; const h = Math.max(4, Math.min(1, n / Math.max(1, s.goal)) * 84);
              const isToday = todayKey(d) === todayKey();
              return (
                <View key={todayKey(d)} style={{ flex: 1, alignItems: 'center', gap: 4 }}>
                  <Text style={{ fontSize: 11, fontWeight: '800', color: C.muted }}>{n || ''}</Text>
                  <View style={{ width: '70%', height: h, borderRadius: 8, backgroundColor: n >= s.goal ? BLUE : isToday ? SKY : 'rgba(47,128,237,0.35)' }} />
                  <Text style={{ fontSize: 11, fontWeight: isToday ? '800' : '600', color: isToday ? C.ink : C.muted }}>{isToday ? 'Today' : d.toLocaleDateString('en-IN', { weekday: 'short' }).slice(0, 3)}</Text>
                </View>
              );
            })}
          </View>
        </Glass>
      </Rise>

      <Rise index={2}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 14 }}>
          <Toggle value={s.enabled} onChange={(v) => update({ enabled: v })} label="Remind me to drink water"
            note={s.enabled ? (next ? `Next reminder at ${fmtTime(next)}${saving ? ' · saving…' : ''}` : `Next reminder tomorrow at ${fmtTime(times[0])}`) : 'Off'} />
          <View style={{ gap: 8, opacity: s.enabled ? 1 : 0.5 }}>
            <Text style={{ fontWeight: '800', color: C.ink }}>Remind me every</Text>
            <Segmented small value={s.every} onChange={(v) => update({ every: v })} options={[[60, '1 hr'], [90, '1½ hrs'], [120, '2 hrs'], [180, '3 hrs']]} />
            <Text style={{ fontWeight: '800', color: C.ink, marginTop: 6 }}>From</Text>
            <Segmented small value={s.start} onChange={(v) => update({ start: v })} options={[[6, '6 AM'], [7, '7 AM'], [8, '8 AM'], [9, '9 AM'], [10, '10 AM']]} />
            <Text style={{ fontWeight: '800', color: C.ink, marginTop: 6 }}>Until</Text>
            <Segmented small value={s.end} onChange={(v) => update({ end: v })} options={[[18, '6 PM'], [19, '7 PM'], [20, '8 PM'], [21, '9 PM'], [22, '10 PM']]} />
            <Text style={{ fontWeight: '800', color: C.ink, marginTop: 6 }}>Daily goal</Text>
            <Segmented small value={s.goal} onChange={(v) => update({ goal: v })} options={[[6, '6 glasses'], [8, '8'], [10, '10'], [12, '12']]} />
          </View>
          {s.enabled ? <Muted style={{ fontSize: 12 }}>{times.length} reminders a day: {times.map(fmtTime).join(' · ')}</Muted> : null}
        </Glass>
      </Rise>

      <Rise index={3}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 8 }}>
          <H2>⌚ On your watch</H2>
          <Muted>
            {Platform.OS === 'ios'
              ? 'Reminders also appear on your Apple Watch when your iPhone is locked. Tap “I drank a glass” or “In 30 min” right on the watch.'
              : 'Reminders also appear on your Wear OS watch (Galaxy Watch, Pixel Watch and others) through the watch’s phone app. Tap “I drank a glass” or “In 30 min” right on the watch.'}
          </Muted>
          <Muted style={{ fontSize: 12 }}>Make sure the watch is set to show notifications from this app.</Muted>
        </Glass>
      </Rise>

      <Muted style={{ fontSize: 12, textAlign: 'center', paddingHorizontal: 8 }}>General guidance only. If your doctor has asked you to limit fluids, follow their advice and keep reminders off.</Muted>
    </Screen>
  );
}
