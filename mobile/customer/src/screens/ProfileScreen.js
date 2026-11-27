import React from 'react';
import { Alert, Linking, Pressable, Text, View } from 'react-native';
import { BASE, api, rupees, whatsappUrl } from '../shared/api';
import { Glass, H1, Muted, Rise } from '../shared/ui';
import { C } from '../shared/theme';
import { Screen } from './_Screen';
import { useAuth } from '../auth';

function Item({ label, note, onPress, danger }) {
  return (
    <Pressable onPress={onPress} accessibilityRole="button" style={{ flexDirection: 'row', alignItems: 'center', paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: 'rgba(18,48,42,0.07)' }}>
      <View style={{ flex: 1 }}><Text style={{ fontWeight: '700', color: danger ? C.badInk : C.ink, fontSize: 15 }}>{label}</Text>{note ? <Muted style={{ fontSize: 12 }}>{note}</Muted> : null}</View>
      <Text style={{ color: C.muted, fontSize: 18 }}>›</Text>
    </Pressable>
  );
}

export default function ProfileScreen({ navigation }) {
  const { user, signOut } = useAuth();
  const del = () => Alert.alert('Delete account?', 'Your profile, health details and addresses are removed. GST invoices are kept as the law requires. For any wallet balance, contact support before deleting.', [{ text: 'Cancel', style: 'cancel' }, { text: 'Delete', style: 'destructive', onPress: async () => { try { await api('/me', { method: 'DELETE' }); await signOut(); } catch (e) { Alert.alert('Could not delete', e.message); } } }]);
  return (
    <Screen>
      <Rise index={0} style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
        <View style={{ width: 64, height: 64, borderRadius: 32, backgroundColor: C.teal, alignItems: 'center', justifyContent: 'center' }}><Text style={{ color: '#fff', fontSize: 26, fontWeight: '800' }}>{(user?.name || '?')[0]}</Text></View>
        <View style={{ flex: 1 }}><H1 style={{ fontSize: 22 }}>{user?.name}</H1><Muted>+91 {user?.phone}{user?.email ? ' · ' + user.email : ''}</Muted></View>
      </Rise>
      <Rise index={1} style={{ flexDirection: 'row', gap: 10 }}>
        <Glass style={{ flex: 1, padding: 14 }}><Muted>Wallet</Muted><Text style={{ fontWeight: '800', fontSize: 20, color: C.ink }}>{rupees(user?.wallet?.balance_paise)}</Text></Glass>
        <Glass style={{ flex: 1, padding: 14 }}><Muted>Reward points</Muted><Text style={{ fontWeight: '800', fontSize: 20, color: C.ink }}>{user?.points || 0}</Text></Glass>
      </Rise>
      <Rise index={2}><Glass style={{ paddingHorizontal: 16 }}>
        <Item label="Wallet & top-up" note="Daily lunch auto-pay, low balance reminder" onPress={() => navigation.navigate('Wallet')} />
        <Item label="Food & health" note={user?.has_health_profile ? 'Diet, allergies, height & weight' : 'Not set up yet'} onPress={() => navigation.navigate('HealthEdit')} />
        <Item label="Saved addresses" onPress={() => navigation.navigate('Addresses')} />
        <Item label="Notifications" note="Push, WhatsApp, SMS, email" onPress={() => navigation.navigate('Notifications')} />
      </Glass></Rise>
      <Rise index={3}><Glass style={{ paddingHorizontal: 16 }}>
        <Item label="Help & support" onPress={() => Linking.openURL(whatsappUrl('Help with Daily Table'))} />
        <Item label="Privacy policy" onPress={() => Linking.openURL(BASE + '/privacy')} />
        <Item label="Refunds & cancellation" onPress={() => Linking.openURL(BASE + '/refunds')} />
        <Item label="Log out" onPress={signOut} />
        <Item label="Delete account" danger onPress={del} />
      </Glass></Rise>
      <Muted style={{ textAlign: 'center', fontSize: 11 }}>Daily Table · v1.0.0</Muted>
    </Screen>
  );
}
