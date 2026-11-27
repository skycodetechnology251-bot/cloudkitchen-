import React from 'react';
import { Pressable, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Glass } from '../shared/ui';
import Icon from '../shared/Icons';
import { R } from '../rt';

const ICON = { Today: 'home', Route: 'route', Profile: 'user' };

/** Floating glass tab bar (blue accent for delivery partners). */
export default function TabBar({ state, navigation }) {
  const insets = useSafeAreaInsets();
  return (
    <View style={{ position: 'absolute', left: 16, right: 16, bottom: Math.max(insets.bottom, 12) + 6 }}>
      <Glass strong style={{ height: 66, borderRadius: 999, flexDirection: 'row', padding: 6, gap: 4 }}>
        {state.routes.map((route, i) => {
          const on = state.index === i;
          return (
            <Pressable key={route.key} accessibilityRole="tab" accessibilityState={{ selected: on }} accessibilityLabel={route.name} onPress={() => navigation.navigate(route.name)}
              style={{ flex: 1, borderRadius: 999, alignItems: 'center', justifyContent: 'center', backgroundColor: on ? 'rgba(31,95,168,0.13)' : 'transparent' }}>
              <Icon name={ICON[route.name]} size={21} color={on ? R.blue : R.muted} />
              <Text style={{ fontSize: 11, fontWeight: '800', color: on ? R.blue : R.muted, marginTop: 2 }}>{route.name}</Text>
            </Pressable>
          );
        })}
      </Glass>
    </View>
  );
}
