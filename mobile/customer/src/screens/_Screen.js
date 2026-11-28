import React, { useRef } from 'react';
import { RefreshControl, ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Backdrop, Glass, IconBtn } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';
import { useKeyboardPad } from '../shared/keyboard';

export function Screen({ children, scroll = true, refreshing, onRefresh, bottom = 120, footer, gap = 16 }) {
  const insets = useSafeAreaInsets();
  const root = useRef(null);
  const kb = useKeyboardPad(root); // keeps text boxes above the keyboard on every phone
  const body = scroll ? (
    <ScrollView contentContainerStyle={{ paddingTop: insets.top + 10, paddingHorizontal: 20, paddingBottom: (footer ? 150 : bottom) + insets.bottom + kb, gap }}
      refreshControl={onRefresh ? <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={C.teal} /> : undefined}
      keyboardShouldPersistTaps="handled" keyboardDismissMode="on-drag" showsVerticalScrollIndicator={false}>{children}</ScrollView>
  ) : <View style={{ flex: 1, paddingTop: insets.top + 10, paddingHorizontal: 20, gap }}>{children}</View>;
  return (
    <View ref={root} collapsable={false} style={{ flex: 1, backgroundColor: C.bg }}>
      <Backdrop />
      {body}
      {footer && !kb ? <Glass strong style={{ position: 'absolute', left: 0, right: 0, bottom: 0, borderRadius: 30, borderBottomLeftRadius: 0, borderBottomRightRadius: 0, padding: 20, paddingBottom: 16 + insets.bottom, gap: 12 }}>{footer}</Glass> : null}
    </View>
  );
}

/** Design header: glass back button, title, optional right item. `center` puts the title in the middle. */
export function Header({ title, onBack, right, center, kicker }) {
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, minHeight: 44 }}>
      {onBack ? <IconBtn label="Back" onPress={onBack} icon={<Icon name="back" />} /> : null}
      <View style={{ flex: 1 }}>
        {kicker ? <Text style={{ fontSize: 12, fontWeight: '800', color: C.muted, letterSpacing: 0.4 }}>{kicker}</Text> : null}
        {title ? <Text accessibilityRole="header" numberOfLines={1} style={{ fontSize: center ? 17 : 24, fontWeight: '800', color: C.ink, textAlign: center ? 'center' : 'left', letterSpacing: -0.3 }}>{title}</Text> : null}
      </View>
      {right || (center && onBack ? <View style={{ width: 44 }} /> : null)}
    </View>
  );
}

/** Step progress bar (sign-up). */
export function Steps({ step, of = 2 }) {
  return (
    <View style={{ flexDirection: 'row', gap: 6 }} accessibilityLabel={`Step ${step} of ${of}`}>
      {Array.from({ length: of }).map((_, i) => <View key={i} style={{ flex: 1, height: 5, borderRadius: 3, backgroundColor: i < step ? C.teal : 'rgba(18,48,42,0.12)' }} />)}
    </View>
  );
}
