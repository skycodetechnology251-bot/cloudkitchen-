import React, { useCallback, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, rupees } from '../shared/api';
import { Chip, Glass, H1, Muted, Rise } from '../shared/ui';
import { C } from '../shared/theme';
import { Screen } from './_Screen';

export const STATUS = {
  pending_payment: ['Awaiting payment', 'warn'], confirmed: ['Confirmed', null], accepted: ['Kitchen accepted', null], preparing: ['Being cooked', null],
  packed: ['Packed', null], picked_up: ['On the way', null], delivered: ['Delivered', null], cancelled: ['Cancelled', 'bad'], failed: ['Delivery problem', 'bad'],
};

export default function OrdersScreen({ navigation }) {
  const [orders, setOrders] = useState([]);
  const [busy, setBusy] = useState(false);
  const load = useCallback(async () => { setBusy(true); try { setOrders((await api('/orders')).data); } catch (e) {} finally { setBusy(false); } }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));
  return (
    <Screen refreshing={busy} onRefresh={load}>
      <H1>Orders</H1>
      {!orders.length && !busy ? <Glass style={{ padding: 20 }}><Muted>No orders yet. Your lunches and GST invoices will appear here.</Muted></Glass> : null}
      {orders.map((o, i) => {
        const [label, tone] = STATUS[o.status] || [o.status, null];
        return (
          <Rise key={o.id} index={Math.min(i, 8)}>
            <Pressable onPress={() => navigation.navigate('Order', { id: o.id })} accessibilityRole="button" accessibilityLabel={`Order ${o.number}, ${label}`}>
              <Glass style={{ padding: 14, gap: 6 }}>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ fontWeight: '800', color: C.ink }}>{new Date(o.service_date || o.created_at).toDateString()}</Text>
                  <Chip label={label} tone={tone} on={!tone && o.status !== 'delivered'} />
                </View>
                <Muted numberOfLines={1}>{(o.items || []).map((it) => `${it.quantity} × ${it.name}`).join(', ')}</Muted>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}><Muted>{o.number}</Muted><Text style={{ fontWeight: '800', color: C.ink }}>{rupees(o.total_paise)}</Text></View>
              </Glass>
            </Pressable>
          </Rise>
        );
      })}
    </Screen>
  );
}
