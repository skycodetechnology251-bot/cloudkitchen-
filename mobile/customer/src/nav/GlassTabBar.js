import React from 'react';
import { Pressable, Text, View } from 'react-native';
import Svg, { Circle, Path } from 'react-native-svg';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Glass } from '../shared/ui';
import { C } from '../shared/theme';
import { useCart } from '../cart';

const ICONS = {
  Today: <Path d="M3 11 12 4l9 7v9h-6v-6H9v6H3z" />,
  Menu: <Path d="M4 11h16a8 8 0 0 1-16 0ZM12 3v4M8 5v2M16 5v2" />,
  Orders: <Path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6" />,
  Profile: <><Circle cx="12" cy="8" r="4" /><Path d="M4 21a8 8 0 0 1 16 0" /></>,
};

/** Floating frosted tab bar with a separate basket button. */
export default function GlassTabBar({ state, navigation }) {
  const insets = useSafeAreaInsets();
  const cart = useCart();
  return (
    <View style={{ position: 'absolute', left: 16, right: 16, bottom: Math.max(insets.bottom, 12) + 6, flexDirection: 'row', gap: 10, alignItems: 'center' }}>
      <Glass strong style={{ flex: 1, height: 66, borderRadius: 999, flexDirection: 'row', padding: 6, gap: 4 }}>
        {state.routes.map((route, i) => {
          const on = state.index === i;
          return (
            <Pressable key={route.key} accessibilityRole="tab" accessibilityState={{ selected: on }} accessibilityLabel={route.name}
              onPress={() => navigation.navigate(route.name)}
              style={{ flex: 1, borderRadius: 999, alignItems: 'center', justifyContent: 'center', backgroundColor: on ? 'rgba(15,92,77,0.13)' : 'transparent' }}>
              <Svg width={21} height={21} viewBox="0 0 24 24" fill="none" stroke={on ? C.teal : '#3F5E57'} strokeWidth={2}>{ICONS[route.name]}</Svg>
              <Text style={{ fontSize: 11, fontWeight: '800', color: on ? C.teal : '#3F5E57', marginTop: 2 }}>{route.name}</Text>
            </Pressable>
          );
        })}
      </Glass>
      <Pressable accessibilityRole="button" accessibilityLabel={`Basket, ${cart.count} items`} onPress={() => navigation.navigate('Checkout')}>
        <Glass strong style={{ width: 66, height: 66, borderRadius: 33, alignItems: 'center', justifyContent: 'center' }}>
          <Svg width={24} height={24} viewBox="0 0 24 24" fill="none" stroke={C.teal} strokeWidth={2}><Path d="M5 8h14l-1.5 12h-11z" /><Path d="M9 8V6a3 3 0 0 1 6 0v2" /></Svg>
          {cart.count > 0 ? <View style={{ position: 'absolute', top: 10, right: 10, minWidth: 18, height: 18, borderRadius: 9, backgroundColor: C.teal, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4 }}><Text style={{ color: '#fff', fontSize: 11, fontWeight: '800' }}>{cart.count}</Text></View> : null}
        </Glass>
      </Pressable>
    </View>
  );
}
