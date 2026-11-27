import React, { useEffect, useRef, useState } from 'react';
import { Pressable, ScrollView, Text, View, useWindowDimensions } from 'react-native';
import { api } from '../shared/api';
import { Btn, Chip, Field, Glass, H2, Muted, Rise, Segmented, Toggle } from '../shared/ui';
import { C } from '../shared/theme';
import { Header, Screen, Steps } from './_Screen';
import { useAuth } from '../auth';

const ALLERGIES = ['Peanut', 'Tree nuts', 'Milk', 'Gluten', 'Sesame', 'Soy', 'Egg', 'Shellfish'];
const CONDITIONS = ['Diabetes', 'High blood pressure', 'High cholesterol', 'Thyroid', 'PCOS', 'Heart care', 'Weight management'];
const TICK = 10;

/** Slide-to-pick ruler: scroll the scale under a fixed centre line. */
function Ruler({ label, value, min, max, step, major, unit, format, onChange, units }) {
  const { width } = useWindowDimensions();
  const w = width - 72;
  const ref = useRef(null);
  const count = Math.round((max - min) / step);
  const toX = (v) => ((v - min) / step) * TICK;
  useEffect(() => { setTimeout(() => ref.current?.scrollTo({ x: toX(value), animated: false }), 50); }, [unit]);
  const nudge = (d) => { const v = Math.min(max, Math.max(min, +(value + d * step).toFixed(1))); onChange(v); ref.current?.scrollTo({ x: toX(v), animated: true }); };
  return (
    <View style={{ gap: 8 }}>
      <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
        <Text style={{ fontWeight: '800', fontSize: 15, color: C.ink }}>{label}</Text>
        {units}
      </View>
      <Text accessibilityLiveRegion="polite" style={{ textAlign: 'center', fontSize: 40, fontWeight: '800', color: C.ink, letterSpacing: -1 }}>{format(value)}<Text style={{ fontSize: 16, color: C.muted, letterSpacing: 0 }}> {unit}</Text></Text>
      <Glass style={{ height: 70, borderRadius: 18 }}>
        <ScrollView ref={ref} horizontal showsHorizontalScrollIndicator={false} snapToInterval={TICK} decelerationRate="fast"
          contentContainerStyle={{ paddingHorizontal: w / 2 }} scrollEventThrottle={16}
          onScroll={(e) => { const v = +(min + Math.round(e.nativeEvent.contentOffset.x / TICK) * step).toFixed(1); if (v !== value && v >= min && v <= max) onChange(v); }}
          accessibilityLabel={`${label} scale`} accessibilityHint="Swipe left or right to change">
          {Array.from({ length: count + 1 }).map((_, i) => {
            const v = min + i * step; const isMajor = Math.abs(v / major - Math.round(v / major)) < 1e-6;
            return (
              <View key={i} style={{ width: TICK, alignItems: 'center' }}>
                <View style={{ width: 2, height: isMajor ? 30 : 16, borderRadius: 2, backgroundColor: isMajor ? C.ink : 'rgba(18,48,42,0.35)' }} />
                {isMajor ? <Text style={{ position: 'absolute', top: 36, width: 50, textAlign: 'center', fontSize: 11, fontWeight: '700', color: C.muted }}>{format(v)}</Text> : null}
              </View>
            );
          })}
        </ScrollView>
        <View pointerEvents="none" style={{ position: 'absolute', left: w / 2 - 1.5, top: 0, width: 3, height: 44, borderBottomLeftRadius: 3, borderBottomRightRadius: 3, backgroundColor: C.teal }} />
      </Glass>
      <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 12, alignItems: 'center' }}>
        <Pressable accessibilityRole="button" accessibilityLabel={`Less ${label.toLowerCase()}`} onPress={() => nudge(-1)} style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(255,255,255,0.9)', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22, fontWeight: '700', color: C.ink }}>−</Text></Pressable>
        <Muted>Slide the scale or tap − / +</Muted>
        <Pressable accessibilityRole="button" accessibilityLabel={`More ${label.toLowerCase()}`} onPress={() => nudge(1)} style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: C.mint, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22, fontWeight: '700', color: C.teal }}>+</Text></Pressable>
      </View>
    </View>
  );
}

export default function HealthScreen({ navigation, route }) {
  const onboarding = route.params?.onboarding;
  const { setUser, refresh } = useAuth();
  const [diet, setDiet] = useState(null);
  const [allergies, setAllergies] = useState([]);
  const [consent, setConsent] = useState(false);
  const [conds, setConds] = useState([]);
  const [kg, setKg] = useState(66);
  const [cm, setCm] = useState(160);
  const [wU, setWU] = useState('kg');
  const [hU, setHU] = useState('cm');
  const [bodySet, setBodySet] = useState(false);
  const [spice, setSpice] = useState('medium');
  const [dislikes, setDislikes] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    api('/me/health').then((h) => {
      if (!h) return;
      setDiet(h.diet); setAllergies((h.allergies || []).map((a) => a[0].toUpperCase() + a.slice(1))); setConsent(!!h.conditions_consent); setConds(h.conditions || []);
      if (h.weight_kg) { setKg(h.weight_kg); setBodySet(true); } if (h.height_cm) setCm(h.height_cm); setSpice(h.spice || 'medium'); setDislikes(h.dislikes || '');
    }).catch(() => {});
  }, []);

  const toggle = (list, set, v) => set(list.includes(v) ? list.filter((x) => x !== v) : [...list, v]);
  const done = async () => { if (onboarding) setUser(route.params.user); else { await refresh(); navigation.goBack(); } };
  const save = async () => {
    try {
      setBusy(true);
      await api('/me/health', { method: 'PUT', body: { diet, allergies: allergies.map((a) => a.toLowerCase()), conditions_consent: consent, conditions: consent ? conds : [], height_cm: bodySet ? cm : null, weight_kg: bodySet ? kg : null, spice, dislikes } });
      await done();
    } finally { setBusy(false); }
  };
  const wUnits = <Segmented small options={[['kg', 'kg'], ['lb', 'lb']]} value={wU} onChange={setWU} />;
  const hUnits = <Segmented small options={[['cm', 'cm'], ['ft', 'ft']]} value={hU} onChange={setHU} />;
  const lb = Math.round(kg * 2.20462);
  const inches = Math.round(cm / 2.54);
  const bmi = (kg / Math.pow(cm / 100, 2)).toFixed(1);

  return (
    <Screen bottom={130} footer={<View style={{ flexDirection: 'row', gap: 10 }}>{onboarding ? <Btn kind="ghost" title="Skip for now" onPress={done} style={{ flex: 1 }} /> : null}<Btn title={busy ? 'Saving…' : onboarding ? 'Save & finish' : 'Save'} onPress={save} disabled={busy} style={{ flex: 1.4 }} /></View>}>
      <Header onBack={() => navigation.goBack()} kicker={onboarding ? 'Step 2 of 2 · Optional' : undefined}
        right={onboarding ? <Pressable onPress={done} hitSlop={10} accessibilityRole="button"><Text style={{ fontWeight: '800', color: C.teal, fontSize: 15 }}>Skip</Text></Pressable> : undefined} />
      {onboarding ? <Steps step={2} /> : null}
      <View><Text accessibilityRole="header" style={{ fontSize: 28, lineHeight: 33, fontWeight: '800', color: C.ink }}>Food & health preferences</Text>
        <Muted style={{ fontSize: 15, lineHeight: 22, marginTop: 6 }}>Help us suggest meals that suit you. Everything here is optional — skip it and you can still order anything.</Muted></View>

      <Rise index={0}><Glass style={{ padding: 16, gap: 16 }}>
        <View><H2>Your body</H2><Muted>Helps us size portions and lets your dietitian set calorie targets. Optional.</Muted></View>
        {wU === 'kg'
          ? <Ruler units={wUnits} label="Weight" unit="kg" value={kg} min={30} max={180} step={0.5} major={5} format={(v) => v.toFixed(1)} onChange={(v) => { setKg(v); setBodySet(true); }} />
          : <Ruler units={wUnits} label="Weight" unit="lb" value={lb} min={66} max={400} step={1} major={10} format={(v) => String(Math.round(v))} onChange={(v) => { setKg(Math.round((v / 2.20462) * 2) / 2); setBodySet(true); }} />}
        {hU === 'cm'
          ? <Ruler units={hUnits} label="Height" unit="cm" value={cm} min={100} max={220} step={1} major={10} format={(v) => String(Math.round(v))} onChange={(v) => { setCm(v); setBodySet(true); }} />
          : <Ruler units={hUnits} label="Height" unit="ft · in" value={inches} min={40} max={87} step={1} major={12} format={(v) => `${Math.floor(v / 12)}' ${Math.round(v % 12)}"`} onChange={(v) => { setCm(Math.round(v * 2.54)); setBodySet(true); }} />}
        <View style={{ backgroundColor: 'rgba(191,232,214,0.55)', borderRadius: 16, padding: 14, gap: 4 }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}><Text style={{ fontWeight: '800', color: C.ink }}>Body mass index</Text><Text style={{ fontWeight: '800', fontSize: 18, color: C.ink }}>{bmi}</Text></View>
          <Muted style={{ fontSize: 12 }}>For your dietitian's reference only. You can update this any time in Profile.</Muted>
        </View>
      </Glass></Rise>

      <Rise index={1} style={{ gap: 10 }}><H2>What do you eat?</H2>
        <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>{[['veg', 'Vegetarian'], ['egg', 'Eggetarian'], ['nonveg', 'Non-veg']].map(([k, l]) => <Chip key={k} label={l} on={diet === k} onPress={() => setDiet(k)} />)}</View></Rise>

      <Rise index={2} style={{ gap: 10 }}><H2>Allergies</H2><Muted>We'll warn you before you add a dish that contains these.</Muted>
        <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap' }}>{ALLERGIES.map((a) => <Chip key={a} label={a} on={allergies.includes(a)} onPress={() => toggle(allergies, setAllergies, a)} />)}</View></Rise>

      <Rise index={3}><Glass style={{ padding: 16, gap: 12 }}>
        <H2>Health conditions</H2>
        <Muted>Used only to suggest dietitian-reviewed meals. Never shown to kitchen staff or riders, never in notifications.</Muted>
        <Toggle value={consent} onChange={setConsent} label="I agree to Daily Table using my health details for meal suggestions" />
        <View style={{ flexDirection: 'row', gap: 8, flexWrap: 'wrap', opacity: consent ? 1 : 0.45 }}>
          {CONDITIONS.map((c) => <Chip key={c} label={c} on={consent && conds.includes(c)} onPress={consent ? () => toggle(conds, setConds, c) : undefined} />)}
        </View>
        {!consent ? <Muted style={{ fontSize: 12 }}>Turn on the switch above to choose conditions.</Muted> : null}
        <Muted style={{ fontSize: 12 }}>Suggestions are not medical advice. For complex medical needs, check with your doctor. Turning the switch off deletes these details.</Muted>
      </Glass></Rise>

      <Rise index={4} style={{ gap: 10 }}><H2>Spice level</H2>
        <View style={{ flexDirection: 'row', gap: 8 }}>{['mild', 'medium', 'spicy'].map((s) => <Chip key={s} label={s[0].toUpperCase() + s.slice(1)} on={spice === s} onPress={() => setSpice(s)} />)}</View></Rise>
      <Rise index={5}><Field label="Anything you don't like?" value={dislikes} onChangeText={setDislikes} placeholder="e.g. bitter gourd, brinjal" /></Rise>
    </Screen>
  );
}
