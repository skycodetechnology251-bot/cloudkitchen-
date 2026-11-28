import React, { useRef } from 'react';
import { RefreshControl, ScrollView, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Backdrop, Glass, IconBtn } from '../shared/ui';
import Icon from '../shared/Icons';
import { R, BLOBS } from '../rt';
import { useKeyboardPad } from '../shared/keyboard';

export function Screen({ children, refreshing, onRefresh, footer, bottom = 120 }) {
  const insets = useSafeAreaInsets();
  const root = useRef(null);
  const kb = useKeyboardPad(root); // keeps text boxes above the keyboard on every phone
  return (
    <View ref={root} collapsable={false} style={{ flex: 1, backgroundColor: R.bg }}>
      <Backdrop colors={BLOBS} />
      <ScrollView contentContainerStyle={{ paddingTop: insets.top + 10, paddingHorizontal: 20, paddingBottom: (footer ? 140 : bottom) + insets.bottom + kb, gap: 14 }}
        refreshControl={onRefresh ? <RefreshControl refreshing={!!refreshing} onRefresh={onRefresh} tintColor={R.blue} /> : undefined} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>
        {children}
      </ScrollView>
      {footer && !kb ? <Glass strong style={{ position: 'absolute', left: 0, right: 0, bottom: 0, borderRadius: 30, borderBottomLeftRadius: 0, borderBottomRightRadius: 0, padding: 20, paddingBottom: 16 + insets.bottom, gap: 10 }}>{footer}</Glass> : null}
    </View>
  );
}

export function Header({ title, kicker, onBack, right }) {
  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, minHeight: 44 }}>
      {onBack ? <IconBtn label="Back" onPress={onBack} icon={<Icon name="back" color={R.ink} />} /> : null}
      <View style={{ flex: 1 }}>
        {kicker ? <Text style={{ fontSize: 12, fontWeight: '800', color: R.muted, letterSpacing: 0.4 }}>{kicker}</Text> : null}
        {title ? <Text accessibilityRole="header" numberOfLines={1} style={{ fontSize: 22, fontWeight: '800', color: R.ink }}>{title}</Text> : null}
      </View>
      {right}
    </View>
  );
}
