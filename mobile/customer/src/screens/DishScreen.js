import React, { useEffect, useRef, useState } from 'react';
import { Animated, Pressable, Text, View } from 'react-native';
import * as SecureStore from 'expo-secure-store';
import { api, rupees } from '../shared/api';
import { Btn, Glass, IconBtn, Muted, Rise, Stepper, Tag } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate, { plateColors } from '../shared/Plate';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';
import { useCart } from '../cart';
import { DietMark } from './MenuScreen';

const INFO = {
  grains: ['slow, steady carbs', '#FBF4E1', '#6B4E0A'],
  protein: ['protein to keep you full', '#FBEAE2', '#8A3A17'],
  veg: ['fibre and vitamins', '#E4F4E9', '#1F6B3B'],
  dairy: ['probiotic, cooling', '#E6F1FB', '#24557F'],
};
const COLORS = { grains: C.grains, protein: C.protein, veg: C.veg, dairy: C.dairy };

export default function DishScreen({ navigation, route }) {
  const cart = useCart();
  const [item, setItem] = useState(route.params?.item || null);
  const [sel, setSel] = useState(null);
  const [fav, setFav] = useState(false);
  const [qty, setQty] = useState(1);
  const spin = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (!item && route.params?.slug) api(`/dishes/${route.params.slug}`).then((d) => setItem({ dish: { ...d, category: d.category?.name }, price_paise: d.price_paise, status: 'view' })).catch(() => {});
    Animated.spring(spin, { toValue: 1, useNativeDriver: true, speed: 5, bounciness: 7 }).start();
  }, []);
  useEffect(() => { if (item) SecureStore.getItemAsync('dt_favs').then((v) => setFav((JSON.parse(v || '[]')).includes(item.dish.id))).catch(() => {}); }, [item?.dish?.id]);

  if (!item) return <Screen><Header onBack={() => navigation.goBack()} title="Meal details" center /></Screen>;
  const d = item.dish;
  const inBasket = cart.qty(item.daily_menu_id);
  const total = ['grains', 'protein', 'veg', 'dairy'].reduce((a, k) => a + (d.balance?.[k] || 0), 0) || 1;
  const parts = [['grains', 'GRAINS'], ['protein', d.diet === 'veg' ? 'DAL' : 'PROTEIN'], ['veg', 'VEG'], ['dairy', 'CURD']]
    .map(([k, l]) => ({ k, l, p: Math.round(((d.balance?.[k] || 0) / total) * 100) })).filter((x) => x.p > 0);
  const cur = parts.find((x) => x.k === sel);
  const toggleFav = async () => {
    const list = JSON.parse((await SecureStore.getItemAsync('dt_favs')) || '[]');
    const next = fav ? list.filter((x) => x !== d.id) : [...list, d.id];
    await SecureStore.setItemAsync('dt_favs', JSON.stringify(next)); setFav(!fav);
  };
  const add = () => { for (let i = 0; i < qty; i++) cart.add(item); navigation.goBack(); };
  const tags = [d.fibre_g >= 8 && 'High fibre', d.protein_g >= 18 && 'High protein', d.sodium === 'low' && 'Low salt', (d.allergens || []).length === 0 && 'No major allergens', d.balance?.dairy > 0 && 'Gut friendly (curd)'].filter(Boolean);
  const nutr = [['Protein', d.protein_g, 30, C.protein], ['Fibre', d.fibre_g, 12, C.veg], ['Carbs', d.carbs_g, 120, C.grains], ['Fat', d.fat_g, 30, C.dairy]];

  return (
    <Screen footer={item.daily_menu_id ? (
      item.status === 'open' ? (
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 14 }}>
          <Stepper qty={qty} max={Math.max(1, item.available - inBasket)} onMinus={() => setQty(Math.max(1, qty - 1))} onPlus={() => setQty(qty + 1)} size={44} />
          <Btn title={`Add to basket · ${rupees(item.price_paise * qty)}`} onPress={add} style={{ flex: 1 }} />
        </View>
      ) : <Btn title="Sold out for today" disabled onPress={() => {}} />
    ) : null}>
      <Header onBack={() => navigation.goBack()} title="Meal details" center right={<IconBtn label={fav ? 'Remove from favourites' : 'Save to favourites'} onPress={toggleFav} icon={<Icon name="heart" color={fav ? C.badInk : C.ink} fill={fav ? C.badInk : 'none'} />} />} />

      <Rise index={0}>
        <Glass style={{ borderRadius: 30, padding: 18, gap: 14 }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
            <Text style={{ fontSize: 12, fontWeight: '800', letterSpacing: 0.8, color: C.teal }}>EXPLORE THE PLATE</Text>
            <Text style={{ fontSize: 13, fontWeight: '800', color: C.ink }}>{d.kcal || '—'} <Text style={{ color: C.muted, fontWeight: '600' }}>kcal approx.</Text></Text>
          </View>
          <Animated.View style={{ alignSelf: 'center', transform: [{ rotate: spin.interpolate({ inputRange: [0, 1], outputRange: ['-20deg', '0deg'] }) }, { scale: spin.interpolate({ inputRange: [0, 1], outputRange: [0.85, 1] }) }] }}>
            <Plate uri={d.image_url} size={230} {...plateColors(d)} label={`Top-down view of ${d.name}: ${parts.map((x) => `${x.l.toLowerCase()} ${x.p}%`).join(', ')}`} />
          </Animated.View>
          <View style={{ flexDirection: 'row', gap: 3, height: 10 }}>
            {parts.map((x) => <View key={x.k} style={{ flex: x.p, borderRadius: 3, backgroundColor: COLORS[x.k], opacity: !sel || sel === x.k ? 1 : 0.2 }} />)}
          </View>
          <View style={{ backgroundColor: 'rgba(255,255,255,0.75)', borderRadius: 16, padding: 12 }} accessibilityLiveRegion="polite">
            <Text style={{ fontSize: 14, color: C.muted, lineHeight: 20 }}><Text style={{ fontWeight: '800', color: C.ink }}>{cur ? `${cur.l[0] + cur.l.slice(1).toLowerCase()} · ${cur.p}% —` : 'Tap a part of the plate'}</Text> {cur ? INFO[cur.k][0] + '.' : 'to see what is in it and why it is there.'}</Text>
          </View>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 10 }}>
            {parts.map((x) => (
              <Pressable key={x.k} onPress={() => setSel(sel === x.k ? null : x.k)} accessibilityRole="button" accessibilityState={{ selected: sel === x.k }}
                style={{ flexBasis: '47%', flexGrow: 1, borderRadius: 16, paddingVertical: 10, paddingHorizontal: 12, minHeight: 60, backgroundColor: INFO[x.k][1], borderWidth: 2, borderColor: sel === x.k ? COLORS[x.k] : 'transparent', opacity: !sel || sel === x.k ? 1 : 0.55 }}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}><View style={{ width: 9, height: 9, borderRadius: 3, backgroundColor: COLORS[x.k] }} /><Text style={{ fontSize: 12, fontWeight: '800', color: INFO[x.k][2] }}>{x.l} · {x.p}%</Text></View>
                <Text style={{ fontSize: 14, fontWeight: '800', color: C.ink, marginTop: 4 }}>{INFO[x.k][0][0].toUpperCase() + INFO[x.k][0].slice(1)}</Text>
              </Pressable>
            ))}
          </View>
        </Glass>
      </Rise>

      <Rise index={1} style={{ gap: 4 }}>
        <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline', gap: 10 }}>
          <Text accessibilityRole="header" style={{ flex: 1, fontSize: 26, fontWeight: '800', color: C.ink, letterSpacing: -0.4 }}>{d.name}</Text>
          <Text style={{ fontSize: 26, fontWeight: '800', color: C.ink }}>{rupees(item.price_paise)}</Text>
        </View>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
          <DietMark diet={d.diet} />
          <Text style={{ fontSize: 14, color: C.muted, fontWeight: '600' }}>{[d.category, d.diet === 'veg' ? 'veg' : d.diet === 'egg' ? 'egg' : 'non-veg', d.reviewed ? 'dietitian reviewed' : null].filter(Boolean).join(' · ')}</Text>
        </View>
      </Rise>

      <Rise index={2} style={{ flexDirection: 'row', gap: 8 }}>
        {nutr.map(([l, v, max, c]) => (
          <Glass key={l} style={{ flex: 1, borderRadius: 16, padding: 10, gap: 4 }}>
            <Text style={{ fontSize: 12, fontWeight: '700', color: C.muted }}>{l}</Text>
            <Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }}>{v ?? '—'} g</Text>
            <View style={{ height: 4, borderRadius: 2, backgroundColor: 'rgba(18,48,42,0.08)' }}><View style={{ width: `${Math.min(100, ((v || 0) / max) * 100)}%`, height: 4, borderRadius: 2, backgroundColor: c }} /></View>
          </Glass>
        ))}
      </Rise>

      {tags.length ? <Rise index={3} style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8 }}>{tags.map((t) => <Tag key={t} label={t} style={{ fontSize: 13, paddingHorizontal: 12, paddingVertical: 7 }} />)}</Rise> : null}

      <Rise index={4}>
        <Glass style={{ borderRadius: 22, padding: 16, gap: 8 }}>
          <Text accessibilityRole="header" style={{ fontSize: 16, fontWeight: '800', color: C.ink }}>Ingredients & allergens</Text>
          <Muted style={{ fontSize: 14, lineHeight: 21 }}>{d.ingredients || d.description || 'Ingredient list coming soon.'}</Muted>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 6 }}>
            {(d.allergens || []).length ? d.allergens.map((a) => <Tag key={a} label={`Contains ${a}`} bg={item.allergy_conflict ? C.bad : C.warn} color={item.allergy_conflict ? C.badInk : C.warnInk} />) : <Tag label="No major allergens declared" />}
          </View>
          {item.allergy_conflict ? <Text style={{ color: C.badInk, fontWeight: '700' }}>This dish contains something on your allergy list.</Text> : null}
        </Glass>
      </Rise>
      <Muted style={{ fontSize: 11 }}>Nutrition values are estimates per serving. Suggestions are not medical advice.</Muted>
    </Screen>
  );
}
