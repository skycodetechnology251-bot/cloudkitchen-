import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Animated, AppState, DeviceEventEmitter, Dimensions, Easing, Image, Modal, Pressable, ScrollView, Text, View } from 'react-native';
import * as Location from 'expo-location';
import { useFocusEffect } from '@react-navigation/native';
import { api, localToday, rupees, windowShort } from '../shared/api';
import { Btn, Glass, H2, IconBtn, Muted, Rise, Tag } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate, { plateColors } from '../shared/Plate';
import { C } from '../shared/theme';
import { Screen } from './_Screen';
import { useAuth } from '../auth';
import * as SecureStore from 'expo-secure-store';
import { useCart } from '../cart';
import { addGlass, getLog, getSettings, todayKey, WATER_EVENT } from '../water';
import { getLog as getWorkoutLog, getSettings as getWorkoutSettings, minutesOn, streak as workoutStreak, WORKOUT_EVENT } from '../workout';
import { getCoach, syncWellness } from '../wellness';

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

/** Today's water: glasses so far, a quick + button, and a way into the reminders. */
function WaterCard({ onOpen }) {
  const [n, setN] = useState(0);
  const [goal, setGoal] = useState(8);
  const [on, setOn] = useState(false);
  const read = useCallback(async () => { const [l, s] = await Promise.all([getLog(), getSettings()]); setN(l[todayKey()] || 0); setGoal(s.goal); setOn(s.enabled); }, []);
  useFocusEffect(useCallback(() => { read(); }, [read]));
  useEffect(() => { const sub = DeviceEventEmitter.addListener(WATER_EVENT, read); return () => sub.remove(); }, [read]);
  const plus = async () => { const l = await addGlass(1); setN(l[todayKey()] || 0); };
  return (
    <Pressable onPress={onOpen} accessibilityRole="button" accessibilityLabel={`Water: ${n} of ${goal} glasses today. Open water reminders`}>
      <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 22, paddingVertical: 12, paddingHorizontal: 14, borderWidth: 1.5, borderColor: 'rgba(86,204,242,0.6)' }}>
        <View style={{ width: 44, height: 44, borderRadius: 14, backgroundColor: '#2F80ED', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22 }}>💧</Text></View>
        <View style={{ flex: 1, gap: 6 }}>
          <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{n} of {goal} glasses of water{n >= goal ? ' · goal reached 🎉' : ''}</Text>
          <View style={{ flexDirection: 'row', gap: 3 }}>{Array.from({ length: goal }, (_, i) => <View key={i} style={{ flex: 1, height: 6, borderRadius: 3, backgroundColor: i < n ? '#2F80ED' : 'rgba(47,128,237,0.15)' }} />)}</View>
          <Text style={{ fontSize: 12, color: '#405A54' }}>{on ? 'Reminders on · phone and watch' : 'Tap to turn on reminders'}</Text>
        </View>
        <Pressable onPress={plus} hitSlop={8} accessibilityRole="button" accessibilityLabel="Add a glass of water" style={{ width: 40, height: 40, borderRadius: 20, backgroundColor: 'rgba(47,128,237,0.12)', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22, fontWeight: '800', color: '#2F80ED' }}>+</Text></Pressable>
      </Glass>
    </Pressable>
  );
}

/** Today's workout minutes and streak. */
function WorkoutCard({ onOpen }) {
  const [m, setM] = useState(0); const [goal, setGoal] = useState(30); const [st, setSt] = useState(0);
  const read = useCallback(async () => { const [l, s] = await Promise.all([getWorkoutLog(), getWorkoutSettings()]); setM(minutesOn(l, todayKey())); setGoal(s.goal); setSt(workoutStreak(l)); }, []);
  useFocusEffect(useCallback(() => { read(); }, [read]));
  useEffect(() => { const sub = DeviceEventEmitter.addListener(WORKOUT_EVENT, read); return () => sub.remove(); }, [read]);
  return (
    <Pressable onPress={onOpen} accessibilityRole="button" accessibilityLabel={`Workout: ${m} of ${goal} minutes today, ${st} day streak`}>
      <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 22, paddingVertical: 12, paddingHorizontal: 14, borderWidth: 1.5, borderColor: 'rgba(242,113,28,0.45)' }}>
        <View style={{ width: 44, height: 44, borderRadius: 14, backgroundColor: '#F2711C', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22 }}>🏃</Text></View>
        <View style={{ flex: 1, gap: 6 }}>
          <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{m >= goal ? `Workout done · ${m} min 🎉` : `${m} of ${goal} active minutes`}</Text>
          <View style={{ height: 6, borderRadius: 3, backgroundColor: 'rgba(242,113,28,0.15)', overflow: 'hidden' }}><View style={{ width: `${Math.min(100, (m / goal) * 100)}%`, height: 6, backgroundColor: '#F2711C' }} /></View>
          <Text style={{ fontSize: 12, color: '#405A54' }}>{st > 0 ? `🔥 ${st}-day streak · keep it going` : 'Move 10 minutes today to start a streak'}</Text>
        </View>
        <Text style={{ fontSize: 22, color: '#F2711C', fontWeight: '800' }}>›</Text>
      </Glass>
    </Pressable>
  );
}

/** The coach's top tip for today (or an invitation to switch it on). */
function CoachCard({ user, kitchenId, onOpen }) {
  const [tip, setTip] = useState(null);
  useFocusEffect(useCallback(() => {
    if (!user?.coach_on) { setTip(null); return; }
    (async () => { try { await syncWellness(user); const c = await getCoach(kitchenId); setTip(c.insights?.find((x) => x.tone === 'warn') || c.insights?.[0] || null); } catch (e) {} })();
  }, [user?.coach_on, kitchenId]));
  const warn = tip?.tone === 'warn';
  return (
    <Pressable onPress={onOpen} accessibilityRole="button">
      <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 12, borderRadius: 22, paddingVertical: 12, paddingHorizontal: 14, borderWidth: 1.5, borderColor: warn ? 'rgba(242,113,28,0.5)' : 'rgba(31,138,76,0.45)' }}>
        <View style={{ width: 44, height: 44, borderRadius: 14, backgroundColor: '#1F8A4C', alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontSize: 22 }}>🤖</Text></View>
        <View style={{ flex: 1, gap: 2 }}>
          <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink }}>{user?.coach_on ? (tip ? `${tip.icon} ${tip.title}` : 'Your wellness coach') : 'Meet your wellness coach'}</Text>
          <Text style={{ fontSize: 12, color: '#405A54' }} numberOfLines={2}>{user?.coach_on ? (tip?.text || 'Log water and workouts to get daily tips.') : 'Water, workout and food tips that learn from your day.'}</Text>
        </View>
        <Text style={{ fontSize: 22, color: '#1F8A4C', fontWeight: '800' }}>›</Text>
      </Glass>
    </Pressable>
  );
}

export default function HomeScreen({ navigation }) {
  const { user, refresh, guest, askLogin } = useAuth();
  const cart = useCart();
  const [banners, setBanners] = useState([]);
  const [popup, setPopup] = useState(null);
  const [address, setAddress] = useState(null);
  const [kitchens, setKitchens] = useState([]);
  const [noService, setNoService] = useState(null);
  const [reload, setReload] = useState(0);
  const asked = useRef(false);
  const lastPos = useRef(null);
  const [branchSheet, setBranchSheet] = useState(false);
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

  const loadMenu = async (a, pickId) => {
    if (!a) { setMenu(null); setKitchens([]); setNoService(null); return; }
    const s = await api('/serviceability', { method: 'POST', body: { lat: a.lat, lng: a.lng } });
    const list = s.kitchens || [];
    setKitchens(list);
    // No open branch delivers here: say so clearly.
    setNoService(list.length ? null : { zone: s.zone || null, where: a.id ? `${a.label} · ${a.area || ''}` : (a.area || 'your current location') });
    // The branch the customer chose for this address (if it still delivers there), else the nearest.
    const saved = pickId || (a.id ? Number(await SecureStore.getItemAsync(`kitchen_for_${a.id}`).catch(() => null)) : 0);
    const k = list.find((x) => x.id === saved) || list[0];
    if (!k) { setMenu(null); return; }
    if (cart.kitchenId && cart.kitchenId !== k.id) cart.clear();
    cart.setKitchenId(k.id);
    const m = await api('/menu', { query: { kitchen_id: k.id } });
    setMenu({ ...m, kitchen: k, slots: s.slots || [] }); setResponse(m.today_response);
  };

  /**
   * Which place to serve:
   * 1. an address the customer picked themselves (until they tap "Use my current location");
   * 2. a saved address they are standing at;
   * 3. their current GPS location (nearest branch connects automatically; saved on checkout);
   * 4. their default address when location is off.
   */
  const pickAddress = async () => {
    const list = guest ? [] : await api('/addresses');
    let pos = null;
    try {
      let perm = await Location.getForegroundPermissionsAsync();
      if (!perm.granted && perm.canAskAgain && !asked.current) { asked.current = true; perm = await Location.requestForegroundPermissionsAsync(); }
      if (perm.granted) pos = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
    } catch (e) {}
    if (pos) lastPos.current = { lat: pos.coords.latitude, lng: pos.coords.longitude };

    let chosen = null; let isHere = false;
    if (cart.manual) chosen = list.find((a) => a.id === cart.addressId) || null;
    if (!chosen && pos) {
      if (!guest) try {
        const r = await api('/addresses/detect', { method: 'POST', body: { lat: pos.coords.latitude, lng: pos.coords.longitude } });
        if (r.address) { chosen = list.find((a) => a.id === r.address.id) || r.address; isHere = true; }
      } catch (e) {}
      if (!chosen) {
        let area = 'Current location';
        try { const [g] = await Location.reverseGeocodeAsync({ latitude: pos.coords.latitude, longitude: pos.coords.longitude }); if (g) area = g.district || g.subregion || g.city || g.name || area; } catch (e) {}
        chosen = { id: null, gps: true, label: 'Current location', area, lat: pos.coords.latitude, lng: pos.coords.longitude };
      }
    }
    if (!chosen) chosen = list.find((a) => a.id === cart.addressId) || list.find((a) => a.is_default) || list[0] || null;
    setAddress(chosen); setHere(isHere);
    if (chosen?.gps) { cart.setGps({ lat: chosen.lat, lng: chosen.lng, area: chosen.area }); cart.setAddressId(null); }
    else { cart.setGps(null); if (chosen) cart.setAddressId(chosen.id); }
    return chosen;
  };

  const useCurrentLocation = () => { cart.setManual(false); setReload((n) => n + 1); };

  const load = useCallback(async () => {
    setBusy(true);
    try {
      const [b, w, p] = await Promise.all([api('/banners', { query: { placement: 'home' } }), guest ? Promise.resolve(null) : api('/wallet').catch(() => null), api('/banners', { query: { placement: 'popup' } })]);
      setBanners(b); setWallet(w);
      if (p[0] && !shown.current) { shown.current = true; setPopup(p[0]); Animated.spring(pop, { toValue: 1, useNativeDriver: true, bounciness: 10 }).start(); }
      await loadMenu(await pickAddress());
      if (!guest) refresh();
    } catch (e) {} finally { setBusy(false); }
  }, [cart.addressId, cart.manual, reload, guest]);

  useFocusEffect(useCallback(() => { load(); }, [load]));
  // Moved about 500 m, or came back to the app: re-check the nearest branch (unless an address was picked by hand).
  useFocusEffect(useCallback(() => {
    let sub = null; let alive = true;
    (async () => {
      try {
        const perm = await Location.getForegroundPermissionsAsync();
        if (!perm.granted || !alive) return;
        sub = await Location.watchPositionAsync({ accuracy: Location.Accuracy.Balanced, distanceInterval: 500, timeInterval: 60000 }, (p) => {
          const prev = lastPos.current; const cur = { lat: p.coords.latitude, lng: p.coords.longitude };
          if (cart.manual || !prev) { lastPos.current = cur; return; }
          const dKm = Math.hypot((cur.lat - prev.lat) * 111, (cur.lng - prev.lng) * 111 * Math.cos(cur.lat * Math.PI / 180));
          if (dKm >= 0.5) { lastPos.current = cur; setReload((n) => n + 1); }
        });
      } catch (e) {}
    })();
    const app = AppState.addEventListener('change', (st) => { if (st === 'active' && !cart.manual) setReload((n) => n + 1); });
    return () => { alive = false; sub?.remove?.(); app.remove(); };
  }, [cart.manual]));
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
        <View style={{ width: 44, height: 44, borderRadius: 22, backgroundColor: C.mint, alignItems: 'center', justifyContent: 'center' }}><Text style={{ fontWeight: '800', fontSize: 17, color: C.ink }}>{first ? first[0] : '🙂'}</Text></View>
        <View style={{ flex: 1 }}>
          <Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }} numberOfLines={1}>{hello}{first ? `, ${first}` : ' 👋'}</Text>
          <Pressable onPress={() => navigation.navigate('Addresses', { pick: true })} accessibilityRole="button" accessibilityLabel="Change delivery address" style={{ flexDirection: 'row', alignItems: 'center', gap: 2 }} hitSlop={6}>
            {address ? <Pulse /> : null}
            <Text numberOfLines={1} style={{ fontSize: 13, color: C.muted, fontWeight: '600', flexShrink: 1 }}>{address ? (address.gps ? `📍 Near ${address.area}` : `${address.label} · ${address.area}${here ? " · you're here" : ''}`) : 'Add a delivery address'}</Text>
            <Icon name="chevron" size={14} color={C.muted} />
          </Pressable>
        </View>
        {guest ? (
          <Pressable onPress={askLogin} accessibilityRole="button" accessibilityLabel="Log in or sign up" style={{ paddingHorizontal: 16, height: 40, borderRadius: 999, backgroundColor: '#1F8A4C', justifyContent: 'center' }}>
            <Text style={{ color: '#fff', fontWeight: '800' }}>Log in</Text>
          </Pressable>
        ) : <IconBtn label="Notifications" onPress={() => navigation.navigate('Notifications')} icon={<Icon name="bell" />} />}
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

      {menu?.kitchen ? (
        <Rise index={2}>
          <Pressable onPress={() => kitchens.length > 1 && setBranchSheet(true)} accessibilityRole="button" accessibilityLabel={`Delivering from ${menu.kitchen.name}${kitchens.length > 1 ? '. Change branch' : ''}`}>
            <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 10, borderRadius: 18, paddingVertical: 10, paddingHorizontal: 14 }}>
              <Text style={{ fontSize: 20 }}>🏪</Text>
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: 12, color: C.muted, fontWeight: '700' }}>Delivering from</Text>
                <Text style={{ fontSize: 15, color: C.ink, fontWeight: '800' }} numberOfLines={1}>{menu.kitchen.name}{menu.kitchen.distance_m ? ` · ${(menu.kitchen.distance_m / 1000).toFixed(1)} km` : ''}</Text>
              </View>
              {kitchens.length > 1 ? <Text style={{ color: C.teal, fontWeight: '800' }}>Change ›</Text> : null}
            </Glass>
          </Pressable>
          {address?.gps ? (
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 8, paddingHorizontal: 4 }}>
              <Muted style={{ flex: 1, fontSize: 12 }}>Using your current location — the nearest branch is connected automatically.</Muted>
              <Pressable onPress={() => navigation.navigate('AddressForm', { at: { lat: address.lat, lng: address.lng } })} accessibilityRole="button" hitSlop={6}><Text style={{ color: C.teal, fontWeight: '800', fontSize: 13 }}>Save this location</Text></Pressable>
            </View>
          ) : cart.manual ? (
            <Pressable onPress={useCurrentLocation} accessibilityRole="button" style={{ marginTop: 8, paddingHorizontal: 4 }} hitSlop={6}><Text style={{ color: C.teal, fontWeight: '800', fontSize: 13 }}>📍 Use my current location instead</Text></Pressable>
          ) : null}
        </Rise>
      ) : null}

      <Modal transparent visible={branchSheet} animationType="slide" onRequestClose={() => setBranchSheet(false)}>
        <Pressable style={{ flex: 1, backgroundColor: 'rgba(0,0,0,0.35)' }} onPress={() => setBranchSheet(false)} accessibilityLabel="Close" />
        <View style={{ backgroundColor: '#fff', borderTopLeftRadius: 28, borderTopRightRadius: 28, padding: 22, gap: 10 }}>
          <Text style={{ fontSize: 20, fontWeight: '800', color: C.ink }}>Choose a branch</Text>
          <Muted>These kitchens deliver to {address?.label || 'your address'}. Each has its own menu.{cart.count ? ' Changing branch empties your basket.' : ''}</Muted>
          {kitchens.map((k) => {
            const on = k.id === menu?.kitchen?.id;
            return (
              <Pressable key={k.id} accessibilityRole="radio" accessibilityState={{ selected: on }}
                onPress={async () => { setBranchSheet(false); if (on) return; await SecureStore.setItemAsync(`kitchen_for_${address.id}`, String(k.id)).catch(() => {}); setBusy(true); try { await loadMenu(address, k.id); } finally { setBusy(false); } }}
                style={{ flexDirection: 'row', alignItems: 'center', gap: 12, padding: 14, borderRadius: 18, borderWidth: 2, borderColor: on ? C.teal : 'rgba(18,48,42,0.1)', backgroundColor: on ? 'rgba(15,92,77,0.06)' : '#fff' }}>
                <Text style={{ fontSize: 22 }}>🏪</Text>
                <View style={{ flex: 1 }}><Text style={{ fontWeight: '800', fontSize: 16, color: C.ink }}>{k.name}</Text><Muted style={{ fontSize: 12 }}>{k.distance_m ? `${(k.distance_m / 1000).toFixed(1)} km away` : ''}</Muted></View>
                {on ? <Text style={{ color: C.teal, fontWeight: '800' }}>✓ Selected</Text> : null}
              </Pressable>
            );
          })}
          <View style={{ height: 10 }} />
        </View>
      </Modal>

      {!address ? (
        <Rise index={2}><Glass style={{ padding: 18, gap: 10, borderRadius: 26 }}><H2>Where should we deliver?</H2><Muted>Add your home or office. We'll check if a Daily Table kitchen delivers there.</Muted><Btn title="Add address" onPress={() => navigation.navigate('AddressForm')} /></Glass></Rise>
      ) : !menu ? (
        <Rise index={2}>
          <Glass style={{ padding: 20, gap: 10, borderRadius: 26, alignItems: 'center' }}>
            <Text style={{ fontSize: 46 }}>📍</Text>
            <Text style={{ fontSize: 20, fontWeight: '800', color: C.ink, textAlign: 'center' }}>Sorry, our service is not available at your location yet</Text>
            <Muted style={{ textAlign: 'center' }}>
              {noService?.zone && noService.zone.status !== 'live'
                ? `${noService.zone.name} is opening soon. We'll start delivering there as soon as a branch is ready.`
                : `No Daily Table branch delivers to ${noService?.where || 'this place'} right now. We are opening new areas one at a time.`}
            </Muted>
            <View style={{ alignSelf: 'stretch', gap: 8, marginTop: 4 }}>
              <Btn title="Choose another address" onPress={() => navigation.navigate('Addresses', { pick: true })} />
              {cart.manual ? <Btn kind="ghost" title="📍 Use my current location" onPress={useCurrentLocation} /> : null}
              {!address.gps ? null : <Btn kind="ghost" title="Add an address" onPress={() => navigation.navigate('AddressForm')} />}
            </View>
          </Glass>
        </Rise>
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

      <Rise index={5} style={{ gap: 10 }}>
        <H2 style={{ fontSize: 18 }}>Your wellness today</H2>
        <CoachCard user={user} kitchenId={cart.kitchenId} onOpen={() => navigation.navigate('Coach')} />
        <WaterCard onOpen={() => navigation.navigate('Water')} />
        <WorkoutCard onOpen={() => navigation.navigate('Workout')} />
      </Rise>

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
