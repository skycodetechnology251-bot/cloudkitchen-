import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Animated, Dimensions, Easing, Image, Modal, Pressable, ScrollView, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { useFocusEffect } from '@react-navigation/native';
import { api, localToday, rupees, windowShort } from '../shared/api';
import { Btn, Glass, H2, IconBtn, Muted, Rise, Tag } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate, { plateColors } from '../shared/Plate';
import { C } from '../shared/theme';
import { Screen } from './_Screen';
import { useAuth } from '../auth';
import { useCart } from '../cart';

const W = Dimensions.get('window').width - 40;
export const PARTS = (diet) => [['grains', 'Grains', C.grains], ['protein', diet === 'veg' ? 'Dal & sambar' : 'Protein', C.protein], ['veg', 'Vegetables', C.veg], ['dairy', 'Curd', C.dairy]];
export const pct = (balance, k) => { const t = ['grains', 'protein', 'veg', 'dairy'].reduce((a, x) => a + (balance?.[x] || 0), 0) || 1; return Math.round(((balance?.[k] || 0) / t) * 100); };

/** Admin-designed banner (Admin → Banners), as in the design's carousel. */
function Banner({ b, onPress }) {
  const d = b.design || {};
  return (
    <Pressable onPress={onPress} accessibilityRole="button" accessibilityLabel={`${b.title}${b.subtitle ? ', ' + b.subtitle : ''}`} style={{ width: W, height: 200, overflow: 'hidden', backgroundColor: d.bg || C.teal, paddingTop: 20, paddingHorizontal: 20, gap: 6 }}>
      <View style={{ position: 'absolute', right: -34, bottom: -40 }}>{b.image_url ? <Image source={{ uri: b.image_url }} style={{ width: 190, height: 190, borderRadius: 95 }} /> : <Plate size={190} {...(d.c1 ? { mound: d.c1 } : {})} />}</View>
      {b.tag ? <Text style={{ fontSize: 11, fontWeight: '800', letterSpacing: 0.8, color: d.accent || C.mint }}>{b.tag}</Text> : null}
      <Text style={{ fontSize: 24, lineHeight: 26, fontWeight: '800', maxWidth: 190, color: d.ink || '#fff' }}>{b.title}</Text>
      {b.subtitle ? <Text style={{ fontSize: 13, fontWeight: '600', maxWidth: 180, color: d.ink || '#CFE6DF', opacity: 0.9 }}>{b.subtitle}</Text> : null}
      {b.cta ? <View style={{ alignSelf: 'flex-start', marginTop: 4, height: 36, paddingHorizontal: 14, borderRadius: 999, justifyContent: 'center', backgroundColor: d.accent || C.mint }}><Text style={{ fontSize: 13, fontWeight: '800', color: d.btnInk || C.ink }}>{b.cta} →</Text></View> : null}
    </Pressable>
  );
}

function Pulse() {
  const a = useRef(new Animated.Value(0)).current;
  useEffect(() => { Animated.loop(Animated.timing(a, { toValue: 1, duration: 1600, easing: Easing.out(Easing.quad), useNativeDriver: true })).start(); }, [a]);
  return (
    <View style={{ width: 8, height: 8, marginRight: 4 }}>
      <Animated.View style={{ position: 'absolute', width: 8, height: 8, borderRadius: 4, backgroundColor: C.teal, opacity: a.interpolate({ inputRange: [0, 1], outputRange: [0.5, 0] }), transform: [{ scale: a.interpolate({ inputRange: [0, 1], outputRange: [1, 2.6] }) }] }} />
      <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: C.teal }} />
    </View>
  );
}

/** "Today's balanced plate" card: the plate, kcal, balance bar and legend. */
function PlateCard({ it, onOpen, onAdd }) {
  const d = it.dish;
  return (
    <Pressable onPress={onOpen} accessibilityRole="button" accessibilityLabel={`${d.name}, ${d.kcal || ''} kilocalories, ${rupees(it.price_paise)}`} style={{ width: W }}>
      <Glass style={{ borderRadius: 28, padding: 18, gap: 14 }}>
        <View style={{ flexDirection: 'row', gap: 16, alignItems: 'center' }}>
          <Plate uri={d.image_url} size={150} {...plateColors(d)} />
          <View style={{ flex: 1, gap: 8 }}>
            <Text style={{ fontSize: 24, fontWeight: '800', color: C.ink }}>{d.kcal || '—'} <Text style={{ fontSize: 12, color: C.muted, fontWeight: '700' }}>kcal approx.</Text></Text>
            <View style={{ flexDirection: 'row', gap: 2, height: 8, borderRadius: 99, overflow: 'hidden' }}>{PARTS(d.diet).map(([k, , c]) => (d.balance?.[k] ? <View key={k} style={{ flex: d.balance[k], backgroundColor: c }} /> : null))}</View>
            {PARTS(d.diet).map(([k, l, c]) => (
              <View key={k} style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                <View style={{ width: 10, height: 10, borderRadius: 3, backgroundColor: c }} />
                <Text style={{ fontSize: 13, fontWeight: '600', color: C.ink, flex: 1 }}>{l}</Text>
                <Text style={{ fontSize: 13, fontWeight: '600', color: C.muted }}>{pct(d.balance, k)}%</Text>
              </View>
            ))}
          </View>
        </View>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', gap: 10 }}>
          <View style={{ flex: 1, gap: 6 }}>
            <Text style={{ fontSize: 19, fontWeight: '800', color: C.ink }} numberOfLines={1}>{d.name}</Text>
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6 }}>
              {it.fits_preferences ? <Tag label="Fits your preferences" /> : null}
              {d.fibre_g >= 8 ? <Tag label="High fibre" bg="rgba(255,255,255,0.8)" color="#27403A" /> : null}
              {it.allergy_conflict ? <Tag label="Has your allergen" bg={C.bad} color={C.badInk} /> : null}
            </View>
          </View>
          {it.status === 'open'
            ? <Pressable onPress={onAdd} accessibilityRole="button" accessibilityLabel={`Add ${d.name}`} style={{ height: 44, paddingHorizontal: 16, borderRadius: 999, backgroundColor: C.teal, flexDirection: 'row', alignItems: 'center', gap: 6 }}><Text style={{ color: '#fff', fontWeight: '800', fontSize: 15 }}>{rupees(it.price_paise)}</Text><Icon name="plus" size={16} color="#fff" width={2.6} /></Pressable>
            : <Tag label="Sold out" bg={C.bad} color={C.badInk} />}
        </View>
      </Glass>
    </Pressable>
  );
}

export default function HomeScreen({ navigation }) {
  const { user, refresh } = useAuth();
  const cart = useCart();
  const [banners, setBanners] = useState([]);
  const [popup, setPopup] = useState(null);
  const [address, setAddress] = useState(null);
  const [here, setHere] = useState(false);
  const [menu, setMenu] = useState(null);
  const [wallet, setWallet] = useState(null);
  const [response, setResponse] = useState(null);
  const [busy, setBusy] = useState(false);
  const [page, setPage] = useState(0);
  const [pick, setPick] = useState(0);
  const pop = useRef(new Animated.Value(0)).current;
  const scroller = useRef(null);
  const shown = useRef(false);

  const loadMenu = async (a) => {
    if (!a?.zone || a.zone.status !== 'live') { setMenu(null); return; }
    const s = await api('/serviceability', { method: 'POST', body: { lat: a.lat, lng: a.lng } });
    const k = s.kitchens?.[0];
    if (!k) { setMenu(null); return; }
    if (cart.kitchenId && cart.kitchenId !== k.id) cart.clear();
    cart.setKitchenId(k.id);
    const m = await api('/menu', { query: { kitchen_id: k.id } });
    setMenu({ ...m, kitchen: k, slots: s.slots || [] }); setResponse(m.today_response);
  };

  /** The saved address the customer is standing at wins; then the one they picked; then their default. */
  const pickAddress = async () => {
    const list = await api('/addresses');
    let chosen = null; let isHere = false;
    try {
      const perm = await Location.getForegroundPermissionsAsync();
      if (perm.granted) {
        const pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        const r = await api('/addresses/detect', { method: 'POST', body: { lat: pos.coords.latitude, lng: pos.coords.longitude } });
        if (r.address) { chosen = list.find((a) => a.id === r.address.id) || r.address; isHere = true; }
      }
    } catch (e) {}
    chosen = (!isHere && list.find((a) => a.id === cart.addressId)) || chosen || list.find((a) => a.is_default) || list[0] || null;
    setAddress(chosen); setHere(isHere); if (chosen) cart.setAddressId(chosen.id);
    return chosen;
  };

  const load = useCallback(async () => {
    setBusy(true);
    try {
      const [b, w, p] = await Promise.all([api('/banners', { query: { placement: 'home' } }), api('/wallet'), api('/banners', { query: { placement: 'popup' } })]);
      setBanners(b); setWallet(w);
      if (p[0] && !shown.current) { shown.current = true; setPopup(p[0]); Animated.spring(pop, { toValue: 1, useNativeDriver: true, bounciness: 10 }).start(); }
      await loadMenu(await pickAddress());
      refresh();
    } catch (e) {} finally { setBusy(false); }
  }, [cart.addressId]);

  useFocusEffect(useCallback(() => { load(); }, [load]));
  useEffect(() => {
    if (banners.length < 2) return;
    const t = setInterval(() => setPage((p) => (p + 1) % banners.length), 4000);
    return () => clearInterval(t);
  }, [banners.length]);
  useEffect(() => { scroller.current?.scrollTo({ x: page * W, animated: true }); }, [page]);

  const tapBanner = (b) => { api(`/banners/${b.id}/tap`, { method: 'POST' }).catch(() => {}); if (b.link?.startsWith('dish:')) navigation.navigate('Dish', { slug: b.link.slice(5) }); else if (b.link === 'wallet') navigation.navigate('Wallet'); else navigation.navigate('Menu'); };
  const answer = async (r) => { setResponse(r); try { await api('/meal-responses', { method: 'POST', body: { response: r, date: menu?.date } }); } catch (e) {} if (r === 'yes') navigation.navigate('Menu'); };
  const addAndGo = (it) => { cart.add(it); navigation.navigate('Checkout'); };

  const picks = (menu?.items || []).filter((i) => i.status === 'open').sort((a, b) => (b.fits_preferences - a.fits_preferences)).slice(0, 3);
  const isToday = menu?.date === localToday();
  const hour = new Date().getHours();
  const hello = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
  const first = (user?.name || '').split(' ')[0];
  const cutoff = picks[0]?.cutoff || '10:45';
  const slotText = windowShort(menu?.slots);
  const lowFor = wallet && wallet.balance_paise < 14000;

  return (
    <Screen refreshing={busy} onRefresh={load}>
      <Rise index={0} style={{ flexDirection: 'row', alignItems: 'center', gap: 12 }}>
        <View style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: C.mint, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', fontSize: 17, color: C.ink }}>{(first || '?')[0]}</Text></View>
        <View style={{ flex: 1 }}>
          <Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }} numberOfLines={1}>{hello}, {first}</Text>
          <Pressable onPress={() => navigation.navigate('Addresses', { pick: true })} accessibilityRole="button" accessibilityLabel="Change delivery address" style={{ flexDirection: 'row', alignItems: 'center', gap: 2 }} hitSlop={6}>
            {address ? <Pulse /> : null}
            <Text numberOfLines={1} style={{ fontSize: 13, color: C.muted, fontWeight: '600', flexShrink: 1 }}>{address ? `${address.label} · ${address.area}${here ? " · you're here" : ''}` : 'Add a delivery address'}</Text>
            <Icon name="chevron" size={14} color={C.muted} />
          </Pressable>
        </View>
        <IconBtn label="Notifications" onPress={() => navigation.navigate('Notifications')} icon={<Icon name="bell" />} />
      </Rise>

      {banners.length ? (
        <Rise index={1}>
          <View style={{ height: 200, borderRadius: 28, overflow: 'hidden' }} accessibilityLabel="Offers and news">
            <ScrollView ref={scroller} horizontal pagingEnabled showsHorizontalScrollIndicator={false} onMomentumScrollEnd={(e) => setPage(Math.round(e.nativeEvent.contentOffset.x / W))}>
              {banners.map((b) => <Banner key={b.id} b={b} onPress={() => tapBanner(b)} />)}
            </ScrollView>
            <View style={{ position: 'absolute', left: 20, bottom: 14, flexDirection: 'row', gap: 6 }}>
              {banners.map((b, i) => <Pressable key={b.id} onPress={() => setPage(i)} accessibilityLabel={`Show banner ${i + 1}`} hitSlop={8}><View style={{ width: i === page ? 22 : 8, height: 8, borderRadius: 4, backgroundColor: i === page ? (b.design?.ink || '#fff') : 'rgba(127,127,127,0.45)' }} /></Pressable>)}
            </View>
          </View>
        </Rise>
      ) : null}

      {!address ? (
        <Rise index={2}><Glass style={{ padding: 18, gap: 10, borderRadius: 26 }}><H2>Where should we deliver?</H2><Muted>Add your home or office. We'll check if a Daily Table kitchen delivers there.</Muted><Btn title="Add address" onPress={() => navigation.navigate('AddressForm')} /></Glass></Rise>
      ) : !menu ? (
        <Rise index={2}><Glass style={{ padding: 18, gap: 8, borderRadius: 26 }}><H2>{address.zone ? `${address.zone.name} is coming soon` : 'Not in our area yet'}</H2><Muted>We're opening areas one at a time. We'll let you know when lunch delivery starts at {address.label}.</Muted></Glass></Rise>
      ) : (
        <Rise index={2}>
          <View style={{ backgroundColor: C.teal, borderRadius: 26, paddingVertical: 16, paddingHorizontal: 18, gap: 12 }}>
            <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline', gap: 8 }}>
              <Text style={{ fontSize: 20, fontWeight: '800', color: '#fff' }}>{isToday ? 'Lunch today?' : 'Lunch tomorrow?'}</Text>
              <Text style={{ fontSize: 12, color: '#CFE6DF', fontWeight: '600' }}>Order by {cutoff} · arrives {slotText}</Text>
            </View>
            <View style={{ flexDirection: 'row', gap: 10 }}>
              <Pressable onPress={() => answer('yes')} accessibilityRole="button" style={{ flex: 1, minHeight: 46, borderRadius: 999, backgroundColor: C.mint, alignItems: 'center', justifyContent: 'center' }}><Text style={{ color: C.ink, fontWeight: '800', fontSize: 15 }}>{response === 'yes' ? 'Yes ✓ show me' : 'Yes, show me'}</Text></Pressable>
              <Pressable onPress={() => answer('no')} accessibilityRole="button" style={{ flex: 1, minHeight: 46, borderRadius: 999, borderWidth: 1.5, borderColor: '#5E9C8E', alignItems: 'center', justifyContent: 'center' }}><Text style={{ color: '#fff', fontWeight: '700', fontSize: 15 }}>{response === 'no' ? 'Skipped ✓' : isToday ? 'Skip today' : 'Skip tomorrow'}</Text></Pressable>
            </View>
            <Text style={{ fontSize: 12, color: '#CFE6DF' }}>Nothing is ordered until you check out.</Text>
          </View>
        </Rise>
      )}

      {wallet && (wallet.low || lowFor) ? (
        <Rise index={3}>
          <Pressable onPress={() => navigation.navigate('Wallet')} accessibilityRole="button">
            <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 22, paddingVertical: 12, paddingHorizontal: 14, borderWidth: 1.5, borderColor: 'rgba(242,207,74,0.9)' }}>
              <View style={{ width: 40, height: 40, borderRadius: 12, backgroundColor: C.amber, alignItems: 'center', justifyContent: 'center' }}><Icon name="wallet" size={20} /></View>
              <View style={{ flex: 1 }}>
                <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>Wallet {rupees(wallet.balance_paise)} · {lowFor ? 'not enough for today' : `${wallet.lunches_covered} lunches left`}</Text>
                <Text style={{ fontSize: 12, color: '#405A54' }}>Top up to keep daily lunch going</Text>
              </View>
              <View style={{ height: 36, paddingHorizontal: 14, borderRadius: 999, backgroundColor: C.ink, justifyContent: 'center' }}><Text style={{ color: '#fff', fontWeight: '800', fontSize: 13 }}>Top up</Text></View>
            </Glass>
          </Pressable>
        </Rise>
      ) : null}

      {picks.length ? (
        <Rise index={4} style={{ gap: 12 }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline' }}>
            <H2 style={{ fontSize: 18 }}>{isToday ? "Today's" : "Tomorrow's"} balanced plate</H2>
            <Text style={{ fontSize: 12, fontWeight: '700', color: C.muted }}>{pick + 1} of {picks.length}{picks.length > 1 ? ' · swipe' : ''}</Text>
          </View>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} onMomentumScrollEnd={(e) => setPick(Math.round(e.nativeEvent.contentOffset.x / W))} style={{ marginHorizontal: -20 }} contentContainerStyle={{ paddingHorizontal: 20 }} snapToInterval={W} decelerationRate="fast">
            {picks.map((it) => <PlateCard key={it.daily_menu_id} it={it} onOpen={() => navigation.navigate('Dish', { item: it })} onAdd={() => addAndGo(it)} />)}
          </ScrollView>
          <Pressable onPress={() => navigation.navigate('Menu')} accessibilityRole="button" style={{ alignSelf: 'center', padding: 8 }}><Text style={{ color: C.teal, fontWeight: '800' }}>See the full menu · {menu.items.length} dishes ›</Text></Pressable>
        </Rise>
      ) : null}

      <Modal transparent visible={!!popup} animationType="fade" onRequestClose={() => setPopup(null)}>
        <View style={{ flex: 1, backgroundColor: 'rgba(10,30,25,0.45)', alignItems: 'center', justifyContent: 'center', padding: 28 }}>
          {popup ? (
            <Animated.View style={{ width: '100%', transform: [{ scale: pop.interpolate({ inputRange: [0, 1], outputRange: [0.85, 1] }) }], opacity: pop }}>
              <Glass strong style={{ padding: 22, gap: 12, alignItems: 'center', borderRadius: 30 }}>
                {popup.image_url ? <Image source={{ uri: popup.image_url }} style={{ width: 150, height: 150, borderRadius: 75 }} /> : <Plate size={150} />}
                {popup.tag ? <Tag label={popup.tag} bg={C.amber} color={C.ink} style={{ alignSelf: 'center' }} /> : null}
                <Text style={{ textAlign: 'center', fontSize: 22, fontWeight: '800', color: C.ink }}>{popup.title}</Text>
                {popup.subtitle ? <Muted style={{ textAlign: 'center' }}>{popup.subtitle}</Muted> : null}
                <Btn title={popup.cta || 'Order now'} onPress={() => { const b = popup; setPopup(null); tapBanner(b); }} style={{ alignSelf: 'stretch' }} />
                <Pressable onPress={() => setPopup(null)} hitSlop={8}><Text style={{ color: C.muted, fontWeight: '700', padding: 6 }}>Not now</Text></Pressable>
              </Glass>
            </Animated.View>
          ) : null}
        </View>
      </Modal>
    </Screen>
  );
}
