import React, { useCallback, useState } from 'react';
import { Alert, Pressable, Text, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import * as Location from 'expo-location';
import { api } from '../shared/api';
import { Btn, Glass, Muted, Rise, Tag } from '../shared/ui';
import Icon from '../shared/Icons';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useCart } from '../cart';

function zoneTag(a, here) {
  if (here) return <Tag label="Here now" bg={C.teal} color="#fff" />;
  if (a.zone?.status === 'live') return <Tag label="Delivers" />;
  if (a.zone) return <Tag label="Opening soon" bg={C.warn} color={C.warnInk} />;
  return <Tag label="Outside our area" bg={C.bad} color={C.badInk} />;
}

export default function AddressesScreen({ navigation, route }) {
  const pick = route.params?.pick;
  const cart = useCart();
  const [list, setList] = useState([]);
  const [hereId, setHereId] = useState(null);
  const load = useCallback(async () => {
    try {
      const l = await api('/addresses'); setList(l);
      const perm = await Location.getForegroundPermissionsAsync();
      if (perm.granted) {
        const pos = await Location.getLastKnownPositionAsync() || await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        const r = await api('/addresses/detect', { method: 'POST', body: { lat: pos.coords.latitude, lng: pos.coords.longitude } });
        setHereId(r.address?.id || null);
      }
    } catch (e) {}
  }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  const choose = (a) => {
    if (!pick) { navigation.navigate('AddressForm', { address: a }); return; }
    if (cart.count && cart.addressId && cart.addressId !== a.id) cart.clear();
    cart.setAddressId(a.id); cart.setManual(true); cart.setGps(null); navigation.goBack();
  };
  const remove = (a) => Alert.alert('Remove address?', `${a.label} will be removed from your list. Past orders keep their address.`, [{ text: 'Keep', style: 'cancel' }, { text: 'Remove', style: 'destructive', onPress: async () => { await api(`/addresses/${a.id}`, { method: 'DELETE' }); load(); } }]);
  const here = list.find((a) => a.id === hereId);
  const selected = cart.addressId || here?.id;

  return (
    <Screen>
      <Header title="Delivery addresses" onBack={() => navigation.goBack()} />
      {here ? (
        <Rise index={0}>
          <View style={{ backgroundColor: C.teal, borderRadius: 22, padding: 14, flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            <View style={{ width: 10, height: 10, borderRadius: 5, backgroundColor: '#fff' }} />
            <View style={{ flex: 1 }}><Text style={{ color: '#fff', fontWeight: '800' }}>You're at {here.label}{here.building ? ` · ${here.building}` : ''}</Text><Text style={{ color: '#CFE6DF', fontSize: 13 }}>We picked this address for today automatically.</Text></View>
          </View>
        </Rise>
      ) : null}

      <Text accessibilityRole="header" style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>Saved addresses</Text>
      {!list.length ? <Muted>No addresses yet. Add your home, work or family address below.</Muted> : null}
      {list.map((a, i) => {
        const on = selected === a.id;
        return (
          <Rise key={a.id} index={i + 1}>
            <Pressable onPress={() => choose(a)} onLongPress={() => remove(a)} accessibilityRole={pick ? 'radio' : 'button'} accessibilityState={pick ? { selected: on } : undefined} accessibilityLabel={`${a.label}, ${a.one_line}`} accessibilityHint={pick ? 'Deliver here' : 'Edit. Long press to remove'}>
              <Glass style={{ borderRadius: 22, padding: 14, flexDirection: 'row', gap: 12, alignItems: 'center', borderWidth: on ? 2 : 1, borderColor: on ? C.teal : 'rgba(255,255,255,0.8)' }}>
                <View style={{ width: 40, height: 40, borderRadius: 12, backgroundColor: on ? C.teal : 'rgba(255,255,255,0.9)', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', fontSize: 16, color: on ? '#fff' : C.ink }}>{a.label[0]}</Text></View>
                <View style={{ flex: 1, gap: 3 }}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}><Text style={{ fontWeight: '800', fontSize: 16, color: C.ink }}>{a.label}</Text>{zoneTag(a, a.id === hereId)}</View>
                  <Text style={{ fontSize: 13, color: C.muted, lineHeight: 18 }}>{a.one_line}{a.landmark ? ` · near ${a.landmark}` : ''}</Text>
                </View>
                {pick ? <View style={{ width: 22, height: 22, borderRadius: 11, borderWidth: 2, borderColor: on ? C.teal : 'rgba(18,48,42,0.3)', alignItems: 'center', justifyContent: 'center' }}>{on ? <View style={{ width: 10, height: 10, borderRadius: 5, backgroundColor: C.teal }} /> : null}</View>
                  : <Icon name="right" size={18} color={C.muted} />}
              </Glass>
            </Pressable>
          </Rise>
        );
      })}
      {pick && list.length > 1 ? <Muted style={{ fontSize: 12 }}>Changing address may change the kitchen; your basket is then cleared.</Muted> : null}

      <Glass style={{ borderRadius: 26, padding: 16, gap: 10 }}>
        <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>Add a new address</Text>
        <Muted>Pin your door on the map, then add flat, street, landmark and pincode for the rider.</Muted>
        <Btn kind="dark" title="Add address" icon={<Icon name="pin" size={18} color="#fff" />} onPress={() => navigation.navigate('AddressForm')} />
      </Glass>
    </Screen>
  );
}
