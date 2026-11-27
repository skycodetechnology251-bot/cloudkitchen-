import React, { useEffect, useRef } from 'react';
import { Animated, Easing, Platform, Pressable, StyleSheet, Text, TextInput, View, AccessibilityInfo } from 'react-native';
import { BlurView } from 'expo-blur';
import { C, radius } from './theme';

// Low-end Android devices get a solid surface instead of real-time blur.
const blurOK = Platform.OS === 'ios' || Platform.Version >= 31;

let reduceMotion = false;
AccessibilityInfo.isReduceMotionEnabled?.().then((v) => { reduceMotion = v; });

/** Frosted "liquid glass" surface. */
export function Glass({ style, children, intensity = 40, tint = 'light', strong = false }) {
  const base = [styles.glass, strong && { backgroundColor: 'rgba(255,255,255,0.72)' }, style];
  if (!blurOK) return <View style={[...base, { backgroundColor: 'rgba(255,255,255,0.92)' }]}>{children}</View>;
  return (
    <View style={[base, { overflow: 'hidden' }]}>
      <BlurView intensity={intensity} tint={tint} style={StyleSheet.absoluteFill} />
      {children}
    </View>
  );
}

/** Slow-drifting colour blobs behind the glass. */
export function Backdrop({ colors = ['rgba(128,214,182,0.75)', 'rgba(244,196,96,0.45)', 'rgba(140,190,240,0.55)'] }) {
  const a = useRef(new Animated.Value(0)).current;
  useEffect(() => {
    if (reduceMotion) return;
    const loop = Animated.loop(Animated.sequence([
      Animated.timing(a, { toValue: 1, duration: 7000, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      Animated.timing(a, { toValue: 0, duration: 7000, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
    ]));
    loop.start();
    return () => loop.stop();
  }, [a]);
  const move = (x, y) => ({ transform: [{ translateX: a.interpolate({ inputRange: [0, 1], outputRange: [0, x] }) }, { translateY: a.interpolate({ inputRange: [0, 1], outputRange: [0, y] }) }, { scale: a.interpolate({ inputRange: [0, 1], outputRange: [1, 1.1] }) }] });
  return (
    <View pointerEvents="none" style={StyleSheet.absoluteFill}>
      <Animated.View style={[styles.blob, { backgroundColor: colors[0], left: -120, top: -90 }, move(24, -30)]} />
      <Animated.View style={[styles.blob, { backgroundColor: colors[1], right: -150, top: 260 }, move(-20, 24)]} />
      <Animated.View style={[styles.blob, { backgroundColor: colors[2], left: -90, top: 560 }, move(18, -18)]} />
    </View>
  );
}

/** Content rises in when a screen opens (staggered by index). */
export function Rise({ index = 0, children, style }) {
  const v = useRef(new Animated.Value(reduceMotion ? 1 : 0)).current;
  useEffect(() => { if (!reduceMotion) Animated.timing(v, { toValue: 1, duration: 550, delay: index * 60, easing: Easing.out(Easing.cubic), useNativeDriver: true }).start(); }, [v, index]);
  return <Animated.View style={[{ opacity: v, transform: [{ translateY: v.interpolate({ inputRange: [0, 1], outputRange: [16, 0] }) }] }, style]}>{children}</Animated.View>;
}

/** Button with a gentle press-in. */
export function Btn({ title, onPress, kind = 'primary', disabled, style, icon, accent = C.teal, small }) {
  const s = useRef(new Animated.Value(1)).current;
  const to = (v) => Animated.spring(s, { toValue: v, useNativeDriver: true, speed: 40, bounciness: 6 }).start();
  const bg = kind === 'primary' ? accent : kind === 'mint' ? C.mint : kind === 'dark' ? C.ink : 'rgba(255,255,255,0.6)';
  const fg = kind === 'primary' || kind === 'dark' ? C.white : C.ink;
  return (
    <Animated.View style={[{ transform: [{ scale: s }] }, style]}>
      <Pressable accessibilityRole="button" accessibilityState={{ disabled: !!disabled }} disabled={disabled} onPress={onPress} onPressIn={() => to(0.97)} onPressOut={() => to(1)}
        style={[styles.btn, small && { minHeight: 40, paddingHorizontal: 14 }, { backgroundColor: disabled ? 'rgba(18,48,42,0.15)' : bg }, kind === 'ghost' && { borderWidth: 1, borderColor: 'rgba(18,48,42,0.14)' }]}>
        {icon}
        <Text style={[styles.btnText, small && { fontSize: 13 }, { color: disabled ? C.muted : fg }]}>{title}</Text>
      </Pressable>
    </Animated.View>
  );
}

export function Chip({ label, on, onPress, tone }) {
  const t = tone === 'warn' ? [C.warn, C.warnInk] : tone === 'bad' ? [C.bad, C.badInk] : on ? [C.teal, C.white] : ['rgba(255,255,255,0.55)', C.ink];
  return (
    <Pressable accessibilityRole={onPress ? 'button' : 'text'} accessibilityState={{ selected: !!on }} onPress={onPress} style={[styles.chip, { backgroundColor: t[0] }, !on && !tone && { borderWidth: 1, borderColor: 'rgba(18,48,42,0.12)' }]}>
      <Text style={{ color: t[1], fontWeight: '800', fontSize: 13 }}>{label}</Text>
    </Pressable>
  );
}

export function Field({ label, error, ...props }) {
  return (
    <View style={{ gap: 6 }}>
      {label ? <Text style={styles.label}>{label}</Text> : null}
      <TextInput placeholderTextColor={C.muted} accessibilityLabel={label} style={[styles.input, error && { borderColor: C.badInk }]} {...props} />
      {error ? <Text style={{ color: C.badInk, fontSize: 12, fontWeight: '700' }}>{error}</Text> : null}
    </View>
  );
}

export function Toggle({ value, onChange, label, note }) {
  const x = useRef(new Animated.Value(value ? 1 : 0)).current;
  useEffect(() => { Animated.spring(x, { toValue: value ? 1 : 0, useNativeDriver: false, speed: 20, bounciness: 8 }).start(); }, [value, x]);
  return (
    <Pressable accessibilityRole="switch" accessibilityState={{ checked: !!value }} accessibilityLabel={label} onPress={() => onChange(!value)} style={styles.toggleRow}>
      <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{label}</Text>{note ? <Text style={{ color: C.muted, fontSize: 12, marginTop: 2 }}>{note}</Text> : null}</View>
      <Animated.View style={[styles.track, { backgroundColor: x.interpolate({ inputRange: [0, 1], outputRange: ['rgba(18,48,42,0.2)', C.teal] }) }]}>
        <Animated.View style={[styles.knob, { left: x.interpolate({ inputRange: [0, 1], outputRange: [3, 23] }) }]} />
      </Animated.View>
    </Pressable>
  );
}

/** Grains / protein / veg / dairy colour bar. */
export function BalanceBar({ balance, height = 6, width = 150 }) {
  if (!balance) return null;
  const parts = [['grains', C.grains], ['protein', C.protein], ['veg', C.veg], ['dairy', C.dairy]];
  return (
    <View accessibilityLabel={`Plate balance: grains ${balance.grains || 0}%, protein ${balance.protein || 0}%, vegetables ${balance.veg || 0}%, dairy ${balance.dairy || 0}%`} style={{ flexDirection: 'row', gap: 2, height, width, borderRadius: 99, overflow: 'hidden' }}>
      {parts.map(([k, c]) => (balance[k] ? <View key={k} style={{ flex: balance[k], backgroundColor: c }} /> : null))}
    </View>
  );
}

/** Round glass icon button (44 pt), as in the design headers. */
export function IconBtn({ icon, onPress, label, size = 44, dark }) {
  const inner = <View style={{ width: size, height: size, alignItems: 'center', justifyContent: 'center' }}>{icon}</View>;
  return (
    <Pressable accessibilityRole="button" accessibilityLabel={label} onPress={onPress} hitSlop={6}>
      {dark ? <View style={{ borderRadius: size, backgroundColor: 'rgba(255,255,255,0.16)' }}>{inner}</View> : <Glass style={{ borderRadius: size }}>{inner}</Glass>}
    </Pressable>
  );
}

/** Two- or three-way segmented switch (kg / lb, ₹150 / ₹300 / ₹500 …). */
export function Segmented({ options, value, onChange, small }) {
  return (
    <View accessibilityRole="radiogroup" style={{ flexDirection: 'row', backgroundColor: 'rgba(18,48,42,0.07)', borderRadius: 999, padding: 3, gap: 2 }}>
      {options.map(([k, l]) => (
        <Pressable key={k} accessibilityRole="radio" accessibilityState={{ selected: value === k }} onPress={() => onChange(k)}
          style={{ minHeight: small ? 32 : 40, paddingHorizontal: small ? 12 : 16, borderRadius: 999, alignItems: 'center', justifyContent: 'center', backgroundColor: value === k ? C.teal : 'transparent', flexGrow: small ? 0 : 1 }}>
          <Text style={{ fontWeight: '800', fontSize: small ? 12 : 14, color: value === k ? '#fff' : C.ink }}>{l}</Text>
        </Pressable>
      ))}
    </View>
  );
}

/** Small rounded label ("Here now", "Low balance", "Fits your preferences"). */
export function Tag({ label, bg = C.mint2, color = C.teal, style }) {
  return <Text style={[{ alignSelf: 'flex-start', fontSize: 11, fontWeight: '800', color, backgroundColor: bg, paddingHorizontal: 9, paddingVertical: 4, borderRadius: 999, overflow: 'hidden' }, style]}>{label}</Text>;
}

/** Thin progress bar. */
export function Progress({ value, color = C.teal, track = 'rgba(18,48,42,0.1)', height = 6 }) {
  return <View style={{ height, borderRadius: 99, backgroundColor: track, overflow: 'hidden' }}><View style={{ width: `${Math.max(0, Math.min(1, value)) * 100}%`, height, borderRadius: 99, backgroundColor: color }} /></View>;
}

/** Round stepper − n + used on menu, meal and basket. */
export function Stepper({ qty, onMinus, onPlus, max = 20, size = 40 }) {
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
      <Pressable accessibilityRole="button" accessibilityLabel="Remove one" onPress={onMinus} style={{ width: size, height: size, borderRadius: size, backgroundColor: 'rgba(255,255,255,0.85)', alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: 'rgba(18,48,42,0.08)' }}><Text style={{ fontSize: 20, fontWeight: '700', color: C.ink }}>−</Text></Pressable>
      <Text accessibilityLiveRegion="polite" style={{ minWidth: 16, textAlign: 'center', fontWeight: '800', fontSize: 16, color: C.ink }}>{qty}</Text>
      <Pressable accessibilityRole="button" accessibilityLabel="Add one" disabled={qty >= max} onPress={onPlus} style={{ width: size, height: size, borderRadius: size, backgroundColor: qty >= max ? 'rgba(18,48,42,0.1)' : C.mint, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 20, fontWeight: '700', color: C.teal }}>+</Text></Pressable>
    </View>
  );
}

export const H1 = (p) => <Text accessibilityRole="header" {...p} style={[{ fontSize: 26, fontWeight: '800', color: C.ink, letterSpacing: -0.4 }, p.style]} />;
export const H2 = (p) => <Text accessibilityRole="header" {...p} style={[{ fontSize: 17, fontWeight: '800', color: C.ink }, p.style]} />;
export const Muted = (p) => <Text {...p} style={[{ fontSize: 13, color: C.muted, lineHeight: 19 }, p.style]} />;

export const styles = StyleSheet.create({
  glass: { backgroundColor: 'rgba(255,255,255,0.55)', borderRadius: radius.lg, borderWidth: 1, borderColor: 'rgba(255,255,255,0.8)', shadowColor: '#12302A', shadowOpacity: 0.07, shadowRadius: 20, shadowOffset: { width: 0, height: 8 }, elevation: 2 },
  blob: { position: 'absolute', width: 330, height: 330, borderRadius: 165, opacity: 0.9 },
  btn: { minHeight: 52, borderRadius: 999, paddingHorizontal: 20, alignItems: 'center', justifyContent: 'center', flexDirection: 'row', gap: 8 },
  btnText: { fontWeight: '800', fontSize: 16 },
  chip: { minHeight: 40, paddingHorizontal: 14, borderRadius: 999, alignItems: 'center', justifyContent: 'center' },
  label: { fontSize: 13, fontWeight: '800', color: '#27403A' },
  input: { minHeight: 50, borderRadius: 14, borderWidth: 1, borderColor: 'rgba(18,48,42,0.14)', backgroundColor: 'rgba(255,255,255,0.75)', paddingHorizontal: 14, fontSize: 16, color: C.ink },
  toggleRow: { flexDirection: 'row', alignItems: 'center', gap: 12, minHeight: 52 },
  track: { width: 50, height: 30, borderRadius: 99 },
  knob: { position: 'absolute', top: 3, width: 24, height: 24, borderRadius: 12, backgroundColor: '#fff', shadowColor: '#000', shadowOpacity: 0.2, shadowRadius: 4, elevation: 2 },
});
