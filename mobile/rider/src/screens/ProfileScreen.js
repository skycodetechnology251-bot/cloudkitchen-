import React from 'react';
import { Alert, Linking, Pressable, Text, View } from 'react-native';
import { SUPPORT } from '../shared/api';
import { Glass, Muted } from '../shared/ui';
import { Screen } from './_Screen';
import { useRider } from '../rider';
import { R } from '../rt';

export default function ProfileScreen() {
  const { user, signOut } = useRider();
  const row = (label, onPress, danger) => (
    <Pressable onPress={onPress} accessibilityRole="button" style={{ paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: 'rgba(19,50,79,0.07)' }}>
      <Text style={{ fontWeight: '700', fontSize: 15, color: danger ? R.badInk : R.ink }}>{label}</Text>
    </Pressable>
  );
  return (
    <Screen>
      <Text accessibilityRole="header" style={{ fontSize: 26, fontWeight: '800', color: R.ink }}>Profile</Text>
      <Glass style={{ borderRadius: 24, padding: 16, flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View style={{ width: 52, height: 52, borderRadius: 26, backgroundColor: R.badge, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', fontSize: 18, color: R.badgeInk }}>{(user?.name || '?')[0]}</Text></View>
        <View style={{ flex: 1 }}><Text style={{ fontSize: 18, fontWeight: '800', color: R.ink }}>{user?.name}</Text><Muted>+91 {user?.phone} · Delivery partner</Muted></View>
      </Glass>
      <Glass style={{ borderRadius: 24, paddingHorizontal: 16 }}>
        {SUPPORT.phone ? row('Call operations', () => Linking.openURL('tel:' + SUPPORT.phone)) : null}
        {row('Log out', () => Alert.alert('Log out?', 'You will go off duty.', [{ text: 'Cancel', style: 'cancel' }, { text: 'Log out', onPress: signOut }]), true)}
      </Glass>
      <Muted style={{ textAlign: 'center', fontSize: 11 }}>Daily Table Rider · v1.0.0</Muted>
    </Screen>
  );
}
