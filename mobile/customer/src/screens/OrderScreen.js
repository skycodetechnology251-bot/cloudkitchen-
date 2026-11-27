import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Animated, Linking, Pressable, RefreshControl, ScrollView, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import * as WebBrowser from 'expo-web-browser';
import { api, rupees, slotLabel, whatsappUrl } from '../shared/api';
import { Backdrop, Btn, Glass, IconBtn, Muted, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';

const STEPS = [['confirmed', 'Confirmed & paid', ['confirmed']], ['preparing', 'Kitchen preparing', ['accepted', 'preparing']], ['packed', 'Packed & checked', ['packed']], ['picked_up', 'Picked up · on the way', ['picked_up']], ['delivered', 'Delivered', ['delivered']]];
const RANK = { pending_payment: -1, confirmed: 0, accepted: 1, preparing: 1, packed: 2, picked_up: 3, delivered: 4 };
const KICKER = { pending_payment: 'WAITING FOR PAYMENT', confirmed: 'CONFIRMED', accepted: 'BEING COOKED', preparing: 'BEING COOKED', packed: 'PACKED & CHECKED', picked_up: 'ON THE WAY', delivered: 'DELIVERED', cancelled: 'CANCELLED', failed: 'DELIVERY PROBLEM' };
const time = (t) => new Date(t).toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit' }).toUpperCase();

export default function OrderScreen({ navigation, route }) {
  const insets = useSafeAreaInsets();
  const [o, setO] = useState(null);
  const [busy, setBusy] = useState(false);
  const pulse = useRef(new Animated.Value(0)).current;
  const load = useCallback(async () => { try { setO(await api(`/orders/${route.params.id}`)); } catch (e) {} }, [route.params.id]);
  useFocusEffect(useCallback(() => { load(); const t = setInterval(load, 20000); return () => clearInterval(t); }, [load]));
  useEffect(() => { Animated.loop(Animated.timing(pulse, { toValue: 1, duration: 1400, useNativeDriver: true })).start(); }, []);

  const back = () => (route.params.fresh ? navigation.navigate('Main', { screen: 'Orders' }) : navigation.goBack());
  const help = () => Linking.openURL(whatsappUrl('Help with Daily Table order ' + (o?.number || '')));
  if (!o) return <View style={{ flex: 1, backgroundColor: C.teal }} />;

  const rank = RANK[o.status] ?? -1;
  const stopped = ['cancelled', 'failed'].includes(o.status);
  const cancellable = ['pending_payment', 'confirmed'].includes(o.status);
  const at = (statuses) => (o.events || []).filter((e) => statuses.includes(e.to_status)).map((e) => e.created_at).sort()[0];
  const title = o.status === 'delivered' ? `Delivered at ${time(at(['delivered']) || o.updated_at)}`
    : stopped ? (o.status === 'cancelled' ? 'Order cancelled' : 'We could not deliver')
    : o.slot ? `Arriving ${slotLabel(o.slot)}` : 'Arriving at lunchtime';
  const cancel = () => Alert.alert('Cancel this order?', 'Paid amount goes back to your Daily Table wallet straight away.', [{ text: 'Keep order', style: 'cancel' }, { text: 'Cancel order', style: 'destructive', onPress: async () => { try { await api(`/orders/${o.id}/cancel`, { method: 'POST', body: { reason: 'Cancelled in app' } }); load(); } catch (e) { Alert.alert('Could not cancel', e.message); } } }]);
  const paidBy = (o.wallet_paise ? 'wallet' : '') + (o.total_paise - (o.wallet_paise || 0) > 0 ? (o.wallet_paise ? ' + UPI' : 'UPI / card') : '');
  const initials = (o.rider?.name || '').split(' ').map((x) => x[0]).join('').slice(0, 2).toUpperCase();

  return (
    <View style={{ flex: 1, backgroundColor: C.bg }}>
      <Backdrop />
      <ScrollView contentContainerStyle={{ paddingBottom: 40 + insets.bottom, gap: 16 }} refreshControl={<RefreshControl refreshing={busy} onRefresh={async () => { setBusy(true); await load(); setBusy(false); }} tintColor="#fff" />}>
        <View style={{ backgroundColor: stopped ? '#6B2A12' : C.teal, borderBottomLeftRadius: 32, borderBottomRightRadius: 32, paddingTop: insets.top + 10, paddingHorizontal: 20, paddingBottom: 20, gap: 14 }}>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            <IconBtn dark label="Back" onPress={back} icon={<Icon name="back" color="#fff" />} />
            <Text style={{ flex: 1, textAlign: 'center', color: '#fff', fontWeight: '800', fontSize: 15 }}>Order {o.number}</Text>
            <Pressable onPress={help} accessibilityRole="button" style={{ height: 44, paddingHorizontal: 16, borderRadius: 999, backgroundColor: 'rgba(255,255,255,0.16)', justifyContent: 'center' }}><Text style={{ color: '#fff', fontWeight: '800' }}>Help</Text></Pressable>
          </View>
          <View>
            <Text style={{ color: C.mint, fontWeight: '800', fontSize: 13, letterSpacing: 0.8 }}>{KICKER[o.status] || o.status.toUpperCase()}</Text>
            <Text accessibilityRole="header" style={{ color: '#fff', fontWeight: '800', fontSize: 28, lineHeight: 32, marginTop: 4 }}>{title}</Text>
          </View>
          {o.rider && !stopped && o.status !== 'delivered' ? (
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 12, backgroundColor: 'rgba(255,255,255,0.12)', borderRadius: 20, padding: 12 }}>
              <View style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: '#E3F1EC', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', color: C.ink }}>{initials}</Text></View>
              <View style={{ flex: 1 }}><Text style={{ color: '#fff', fontWeight: '800', fontSize: 16 }}>{o.rider.name}</Text><Text style={{ color: '#CFE6DF', fontSize: 12 }}>Your delivery partner</Text></View>
              {o.rider.phone ? <Pressable onPress={() => Linking.openURL('tel:' + o.rider.phone)} accessibilityRole="button" accessibilityLabel={`Call ${o.rider.name}`} style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: 'rgba(191,232,214,0.9)', alignItems: 'center', justifyContent: 'center' }}><Icon name="phone" size={18} color={C.teal} /></Pressable> : null}
            </View>
          ) : null}
        </View>

        <View style={{ paddingHorizontal: 20, gap: 16 }}>
          {route.params.fresh && rank >= 0 && rank < 4 ? <Rise index={0}><Glass style={{ padding: 14, borderRadius: 20, flexDirection: 'row', gap: 10, alignItems: 'center' }}><Text style={{ fontSize: 26 }}>🎉</Text><View style={{ flex: 1 }}><Text style={{ fontWeight: '800', color: C.ink }}>Lunch is booked!</Text><Muted>We'll notify you when it's on the way.</Muted></View></Glass></Rise> : null}

          {o.delivery_code ? (
            <Rise index={1}>
              <Glass style={{ borderRadius: 22, padding: 16, flexDirection: 'row', alignItems: 'center' }}>
                <View style={{ flex: 1 }}>
                  <Text style={{ fontSize: 13, color: C.muted, fontWeight: '600' }}>Share this code on delivery</Text>
                  <Text accessibilityLabel={`Delivery code ${o.delivery_code.split('').join(' ')}`} style={{ fontSize: 32, fontWeight: '800', letterSpacing: 16, color: C.ink, marginTop: 4 }}>{o.delivery_code}</Text>
                </View>
                <Icon name="lock" size={30} color={C.teal} />
              </Glass>
            </Rise>
          ) : null}

          <Rise index={2}>
            <Glass style={{ borderRadius: 24, padding: 18 }}>
              <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink, marginBottom: 10 }}>Order status</Text>
              {stopped ? <Text style={{ color: C.badInk, fontWeight: '700' }}>{o.status === 'cancelled' ? 'This order was cancelled. Any amount paid has been refunded.' : 'The rider could not complete delivery. Support will contact you.'}</Text>
                : STEPS.map(([k, l, sts], i) => {
                  const done = i <= rank; const now = i === rank && o.status !== 'delivered'; const t = at(sts);
                  return (
                    <View key={k} style={{ flexDirection: 'row', gap: 12 }}>
                      <View style={{ alignItems: 'center', width: 22 }}>
                        <View style={{ width: 22, height: 22, borderRadius: 11, backgroundColor: done && !now ? C.teal : '#fff', borderWidth: now ? 2.5 : done ? 0 : 1.5, borderColor: now ? C.teal : 'rgba(18,48,42,0.18)', alignItems: 'center', justifyContent: 'center' }}>
                          {now ? <Animated.View style={{ position: 'absolute', width: 22, height: 22, borderRadius: 11, backgroundColor: C.teal, opacity: pulse.interpolate({ inputRange: [0, 1], outputRange: [0.4, 0] }), transform: [{ scale: pulse.interpolate({ inputRange: [0, 1], outputRange: [1, 2] }) }] }} /> : null}
                          {done && !now ? <Icon name="check" size={13} color="#fff" width={3} /> : null}
                        </View>
                        {i < STEPS.length - 1 ? <View style={{ width: 2, flex: 1, minHeight: 22, backgroundColor: i < rank ? C.teal : 'rgba(18,48,42,0.12)' }} /> : null}
                      </View>
                      <Text style={{ flex: 1, fontWeight: done ? '800' : '500', fontSize: 15, color: done ? C.ink : C.muted, paddingBottom: 18 }}>{l}</Text>
                      <Text style={{ fontSize: 14, color: C.muted }}>{t && done ? time(t) : '—'}</Text>
                    </View>
                  );
                })}
              {o.status === 'pending_payment' ? <Muted>Waiting for payment confirmation.</Muted> : null}
            </Glass>
          </Rise>

          <Rise index={3}>
            <Glass style={{ borderRadius: 22, padding: 16, flexDirection: 'row', alignItems: 'center', gap: 10 }}>
              <View style={{ flex: 1 }}>
                <Text style={{ fontWeight: '800', fontSize: 15, color: C.ink }} numberOfLines={2}>{(o.items || []).map((it) => `${it.name} × ${it.quantity}`).join(', ')}</Text>
                <Text style={{ fontSize: 12, color: C.muted }}>{rank >= 0 || o.status === 'delivered' ? `Paid ${rupees(o.total_paise)} via ${paidBy}` : `${rupees(o.total_paise)} · not paid yet`}</Text>
              </View>
              {o.invoice_url ? <Pressable onPress={() => WebBrowser.openBrowserAsync(o.invoice_url)} accessibilityRole="button" accessibilityLabel={`GST invoice ${o.invoice?.number || ''}`} hitSlop={8}><Text style={{ color: C.teal, fontWeight: '800' }}>Receipt</Text></Pressable> : null}
            </Glass>
          </Rise>

          {cancellable ? <Btn kind="ghost" title="Cancel order" onPress={cancel} /> : null}
        </View>
      </ScrollView>
    </View>
  );
}
