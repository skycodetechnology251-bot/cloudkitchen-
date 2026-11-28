import React, { useCallback, useEffect, useState } from 'react';
import { Alert, Linking, Modal, Pressable, Text, TextInput, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, BASE, localToday, rupees, slotLabel } from '../shared/api';
import { Btn, Glass, Muted, Rise, Stepper, Tag, Toggle } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate, { plateColors } from '../shared/Plate';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useCart } from '../cart';
import { useAuth } from '../auth';

function Row({ l, v, bold, tone }) {
  return (
    <View style={{ flexDirection: 'row', justifyContent: 'space-between', paddingVertical: bold ? 6 : 3 }}>
      <Text style={{ color: tone || (bold ? C.ink : C.muted), fontWeight: bold ? '800' : '500', fontSize: bold ? 17 : 15 }}>{l}</Text>
      <Text style={{ color: tone || C.ink, fontWeight: bold ? '800' : '600', fontSize: bold ? 17 : 15 }}>{v}</Text>
    </View>
  );
}

export default function CheckoutScreen({ navigation }) {
  const cart = useCart();
  const { refresh } = useAuth();
  const [address, setAddress] = useState(null);
  const [slots, setSlots] = useState([]);
  const [slot, setSlot] = useState(null);
  const [codeOpen, setCodeOpen] = useState(false);
  const [code, setCode] = useState('');
  const [applied, setApplied] = useState('');
  const [points, setPoints] = useState(false);
  const [pay, setPay] = useState('auto');
  const [payOpen, setPayOpen] = useState(false);
  const [quote, setQuote] = useState(null);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  useFocusEffect(useCallback(() => {
    // Delivering to the current location (not saved yet): ask to save it, never fall back to another address.
    if (cart.gps && !cart.addressId) { setAddress(null); return; }
    api('/addresses').then((l) => setAddress(l.find((a) => a.id === cart.addressId) || l.find((a) => a.is_default) || l[0] || null)).catch(() => {});
  }, [cart.addressId]));

  // Delivery windows come from the server (Admin can change them).
  useEffect(() => {
    if (!address) return;
    api('/serviceability', { method: 'POST', body: { lat: address.lat, lng: address.lng, meal: cart.meal || 'lunch' } })
      .then((s) => { setSlots(s.slots || []); setSlot((cur) => ((s.slots || []).includes(cur) ? cur : (s.slots || [])[0] || null)); }).catch(() => {});
  }, [address?.id]);

  const body = () => ({ kitchen_id: cart.kitchenId, items: cart.payload(), offer_code: applied || null, use_points: points, address_id: address?.id, slot, pay });
  useEffect(() => {
    if (!cart.count || !cart.kitchenId) { setQuote(null); return; }
    let live = true;
    api('/checkout/quote', { method: 'POST', body: body() }).then((q) => { if (live) { setQuote(q); setError(''); } })
      .catch((e) => { if (live) { setError(e.message); if (e.errors?.offer_code) setApplied(''); } });
    return () => { live = false; };
  }, [cart.lines, applied, points, address?.id, pay]);

  const place = async (ack = false) => {
    if (!address) { navigation.navigate('AddressForm', cart.gps ? { at: cart.gps } : undefined); return; }
    if (address.zone?.status !== 'live') { setError("We don't deliver to this address yet."); return; }
    try {
      setBusy(true); setError('');
      const r = await api('/checkout', { method: 'POST', body: { ...body(), allergy_acknowledged: ack } });
      cart.clear();
      if (r.payment) navigation.replace('Pay', { payment: r.payment, orderId: r.order.id, purpose: 'order' });
      else { refresh(); navigation.replace('Order', { id: r.order.id, fresh: true }); }
    } catch (e) {
      if (e.errors?.allergy) Alert.alert('Allergy check', e.message, [{ text: 'Review basket', style: 'cancel' }, { text: 'I understand, order', style: 'destructive', onPress: () => place(true) }]);
      else setError(e.message);
    } finally { setBusy(false); }
  };

  const lines = Object.values(cart.lines);
  if (!lines.length) return (
    <Screen><Header title="Review your order" onBack={() => navigation.goBack()} />
      <Glass style={{ padding: 24, alignItems: 'center', gap: 10, borderRadius: 28 }}><Plate size={120} /><Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }}>Your basket is empty</Text><Muted>Add a dish from the menu.</Muted><Btn title="See menu" onPress={() => navigation.navigate('Main', { screen: 'Menu' })} /></Glass>
    </Screen>
  );
  const walletUse = quote && pay !== 'razorpay' ? quote.wallet.would_use_paise : 0;
  const rest = quote ? quote.total_paise - walletUse : 0;
  const payText = !quote ? '…' : pay === 'razorpay' ? `UPI or card ${rupees(quote.total_paise)}` : rest > 0 ? (walletUse ? `Wallet ${rupees(walletUse)} + UPI ${rupees(rest)}` : `UPI or card ${rupees(rest)}`) : `Wallet ${rupees(walletUse)}`;
  const tax = quote ? quote.cgst_paise + quote.sgst_paise : 0;

  return (
    <Screen footer={
      <>
        {error ? <Text style={{ color: C.badInk, fontWeight: '700' }} accessibilityLiveRegion="assertive">{error}</Text> : null}
        <Pressable onPress={() => setPayOpen(true)} accessibilityRole="button" accessibilityLabel={`Paying with ${payText}. Change`} style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
          <Tag label={pay === 'razorpay' ? 'UPI / CARD' : 'WALLET'} bg={C.teal} color="#fff" />
          <Text style={{ flex: 1, fontWeight: '700', fontSize: 15, color: C.ink }}>{payText}</Text>
          <Text style={{ color: C.teal, fontWeight: '800' }}>Change</Text>
        </Pressable>
        <Btn title={busy ? 'Placing order…' : quote ? `Pay ${rupees(quote.total_paise)}` : 'Checking price…'} icon={<Icon name="lock" size={18} color="#fff" />} onPress={() => place(false)} disabled={busy || !quote} />
      </>
    }>
      <Header title="Review your order" onBack={() => navigation.goBack()} />

      <Rise index={0}>
        <Pressable onPress={() => (!address && cart.gps ? navigation.navigate('AddressForm', { at: cart.gps }) : navigation.navigate('Addresses', { pick: true }))} accessibilityRole="button" accessibilityLabel="Change delivery address">
          <Glass style={{ borderRadius: 22, padding: 14, flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            <View style={{ width: 40, height: 40, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.85)', alignItems: 'center', justifyContent: 'center' }}><Icon name={address?.label === 'Home' ? 'home' : 'pin'} size={20} /></View>
            <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 16, color: C.ink }}>{address?.label || (cart.gps ? 'Save your current location' : 'Add a delivery address')}</Text><Text numberOfLines={2} style={{ fontSize: 13, color: C.muted }}>{address ? address.one_line : 'Needed to check delivery'}</Text></View>
            <Text style={{ color: C.teal, fontWeight: '800' }}>{address ? 'Change' : 'Add'}</Text>
          </Glass>
        </Pressable>
      </Rise>

      {slots.length ? (
        <Rise index={1}>
          <Glass style={{ borderRadius: 22, padding: 14, gap: 10 }}>
            <Text style={{ fontWeight: '800', fontSize: 16, color: C.ink }}>Delivery time · {quote?.service_date && quote.service_date !== localToday() ? 'Tomorrow' : 'Today'}</Text>
            <View style={{ flexDirection: 'row', gap: 10 }} accessibilityRole="radiogroup">
              {slots.map((s) => (
                <Pressable key={s} onPress={() => setSlot(s)} accessibilityRole="radio" accessibilityState={{ selected: slot === s }}
                  style={{ flex: 1, minHeight: 56, borderRadius: 14, alignItems: 'center', justifyContent: 'center', borderWidth: slot === s ? 2 : 1, borderColor: slot === s ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: slot === s ? 'rgba(227,241,236,0.95)' : 'rgba(255,255,255,0.85)' }}>
                  <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{slotLabel(s)}</Text>
                  <Text style={{ fontSize: 12, color: C.muted }}>{slot === s ? 'Selected' : 'Available'}</Text>
                </Pressable>
              ))}
            </View>
          </Glass>
        </Rise>
      ) : null}

      {lines.map(({ item, qty }, i) => (
        <Rise key={item.daily_menu_id} index={2 + i}>
          <Glass style={{ borderRadius: 22, padding: 14, flexDirection: 'row', alignItems: 'center', gap: 12 }}>
            <Plate uri={item.dish.image_url} size={52} {...plateColors(item.dish)} />
            <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 15, color: C.ink }} numberOfLines={1}>{item.dish.name}</Text><Text style={{ fontSize: 12, color: C.muted }}>{rupees(item.price_paise)} × {qty}</Text></View>
            <Stepper size={34} qty={qty} max={item.available} onMinus={() => cart.remove(item.daily_menu_id)} onPlus={() => cart.add(item)} />
          </Glass>
        </Rise>
      ))}
      {(quote?.warnings || []).map((w, i) => <View key={i} style={{ backgroundColor: C.bad, borderRadius: 14, padding: 12 }}><Text style={{ color: C.badInk, fontWeight: '700' }}>{w.type === 'allergy' ? `${w.dish} contains ${w.allergens.join(', ')}` : `${w.dish} is not vegetarian`}</Text></View>)}

      <Rise index={4}>
        <Glass style={{ borderRadius: 22, padding: 14, gap: 4 }}>
          {codeOpen || applied ? (
            <View style={{ flexDirection: 'row', gap: 8, alignItems: 'center', marginBottom: 6 }}>
              <TextInput value={code} onChangeText={(t) => setCode(t.toUpperCase())} autoCapitalize="characters" placeholder="Offer code" placeholderTextColor={C.muted} accessibilityLabel="Offer code" style={{ flex: 1, minHeight: 44, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.85)', paddingHorizontal: 12, fontSize: 15, fontWeight: '700', color: C.ink }} />
              <Pressable onPress={() => { if (applied) { setApplied(''); setCode(''); setCodeOpen(false); } else setApplied(code.trim()); }} style={{ height: 44, paddingHorizontal: 16, borderRadius: 999, backgroundColor: C.mint, justifyContent: 'center' }}><Text style={{ color: C.teal, fontWeight: '800' }}>{applied ? 'Remove' : 'Apply'}</Text></Pressable>
            </View>
          ) : <Pressable onPress={() => setCodeOpen(true)} style={{ paddingVertical: 6 }}><Text style={{ color: C.teal, fontWeight: '800' }}>+ Add an offer code</Text></Pressable>}
          {quote?.offer ? <Text style={{ color: C.teal, fontWeight: '800', marginBottom: 4 }}>✓ {quote.offer.title}</Text> : null}
          <Toggle value={points} onChange={setPoints} label="Use my reward points" note="100 points = ₹25 · up to 20% of the order" />
        </Glass>
      </Rise>

      {quote ? (
        <Rise index={5}>
          <Glass style={{ borderRadius: 22, padding: 14 }}>
            <Row l="Meals" v={rupees(quote.subtotal_paise, 2)} />
            {quote.offer_discount_paise ? <Row l="Offer" v={'− ' + rupees(quote.offer_discount_paise, 2)} tone={C.teal} /> : null}
            {quote.points_value_paise ? <Row l={`Points (${quote.points_used})`} v={'− ' + rupees(quote.points_value_paise, 2)} tone={C.teal} /> : null}
            <Row l="Delivery fee" v={quote.delivery_paise ? rupees(quote.delivery_paise, 2) : 'Free'} />
            <Row l="Taxes (GST 5%)" v={rupees(tax, 2)} />
            {quote.round_off_paise ? <Row l="Round off" v={rupees(quote.round_off_paise, 2)} /> : null}
            <View style={{ height: 1, backgroundColor: 'rgba(18,48,42,0.1)', marginVertical: 6 }} />
            <Row l="To pay" v={rupees(quote.total_paise, 2)} bold />
          </Glass>
        </Rise>
      ) : null}
      <View style={{ flexDirection: 'row', gap: 8, alignItems: 'flex-start' }}>
        <Icon name="info" size={16} color={C.muted} />
        <Text style={{ flex: 1, fontSize: 13, color: C.muted, lineHeight: 19 }}>Free cancellation until {lines[0]?.item.cutoff || '10:45'} AM. After cooking starts, contact support for help. <Text onPress={() => Linking.openURL(BASE + '/refunds')} style={{ textDecorationLine: 'underline', color: C.ink }}>Refund policy</Text></Text>
      </View>

      <Modal transparent visible={payOpen} animationType="slide" onRequestClose={() => setPayOpen(false)}>
        <Pressable style={{ flex: 1, backgroundColor: 'rgba(10,30,25,0.35)' }} onPress={() => setPayOpen(false)} accessibilityLabel="Close" />
        <Glass strong style={{ borderRadius: 30, borderBottomLeftRadius: 0, borderBottomRightRadius: 0, padding: 20, paddingBottom: 36, gap: 10 }}>
          <Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }}>Pay with</Text>
          {[['auto', 'Wallet first', `Balance ${rupees(quote?.wallet?.balance_paise)} · any shortfall by UPI or card`], ['razorpay', 'UPI or card', 'Google Pay, PhonePe, Paytm, cards, net banking']].map(([k, t, n]) => (
            <Pressable key={k} onPress={() => { setPay(k); setPayOpen(false); }} accessibilityRole="radio" accessibilityState={{ selected: pay === k }}
              style={{ flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14, borderRadius: 16, borderWidth: pay === k ? 2 : 1, borderColor: pay === k ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: 'rgba(255,255,255,0.85)' }}>
              <View style={{ width: 22, height: 22, borderRadius: 11, borderWidth: 2, borderColor: pay === k ? C.teal : 'rgba(18,48,42,0.3)', alignItems: 'center', justifyContent: 'center' }}>{pay === k ? <View style={{ width: 10, height: 10, borderRadius: 5, backgroundColor: C.teal }} /> : null}</View>
              <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 15, color: C.ink }}>{t}</Text><Text style={{ fontSize: 12, color: C.muted }}>{n}</Text></View>
            </Pressable>
          ))}
          <Pressable onPress={() => { setPayOpen(false); navigation.navigate('Wallet'); }}><Text style={{ color: C.teal, fontWeight: '800', padding: 6 }}>Top up wallet</Text></Pressable>
        </Glass>
      </Modal>
    </Screen>
  );
}
