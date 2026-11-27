import React, { useCallback, useMemo, useState } from 'react';
import { Alert, Pressable, ScrollView, Text, TextInput, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, localToday, rupees } from '../shared/api';
import { BalanceBar, Glass, Muted, Rise, Stepper, Tag } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate, { plateColors } from '../shared/Plate';
import { C } from '../shared/theme';
import { Screen } from './_Screen';
import { useCart } from '../cart';

/** Green (veg) / red (non-veg) / amber (egg) food mark, as on Indian packaging. */
export function DietMark({ diet }) {
  const c = diet === 'veg' ? '#1E8A5A' : diet === 'egg' ? '#C98A12' : '#A33';
  return <View accessibilityLabel={diet === 'veg' ? 'Vegetarian' : diet === 'egg' ? 'Contains egg' : 'Non-vegetarian'} style={{ width: 14, height: 14, borderRadius: 3, borderWidth: 1.6, borderColor: c, alignItems: 'center', justifyContent: 'center' }}><View style={{ width: 6, height: 6, borderRadius: 3, backgroundColor: c }} /></View>;
}

/** Add button that turns into − n + once in the basket. Warns before adding an allergen. */
export function AddButton({ item }) {
  const cart = useCart();
  const q = cart.qty(item.daily_menu_id);
  const add = () => {
    if (item.allergy_conflict && !q) {
      Alert.alert('Contains your allergen', `${item.dish.name} contains ${(item.dish.allergens || []).join(', ')}. Add it anyway?`, [{ text: 'Cancel', style: 'cancel' }, { text: 'Add anyway', style: 'destructive', onPress: () => cart.add(item) }]);
    } else cart.add(item);
  };
  if (item.status !== 'open') return <View style={{ height: 40, paddingHorizontal: 16, borderRadius: 999, borderWidth: 1, borderColor: 'rgba(18,48,42,0.12)', justifyContent: 'center' }}><Text style={{ color: C.muted, fontWeight: '700', fontSize: 13 }}>Sold out</Text></View>;
  if (!q) return <Pressable onPress={add} accessibilityRole="button" accessibilityLabel={`Add ${item.dish.name}`} style={{ height: 40, paddingHorizontal: 18, borderRadius: 999, backgroundColor: C.mint, justifyContent: 'center' }}><Text style={{ color: C.teal, fontWeight: '800', fontSize: 14 }}>Add</Text></Pressable>;
  return <Stepper size={34} qty={q} max={item.available} onMinus={() => cart.remove(item.daily_menu_id)} onPlus={add} />;
}

export default function MenuScreen({ navigation }) {
  const cart = useCart();
  const [data, setData] = useState(null);
  const [cat, setCat] = useState('All');
  const [q, setQ] = useState('');
  const [onlyMine, setOnlyMine] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    if (!cart.kitchenId) { setError('Pick a delivery address on the Today tab to see the menu for your area.'); return; }
    setBusy(true); setError('');
    try { setData(await api('/menu', { query: { kitchen_id: cart.kitchenId } })); } catch (e) { setError(e.message); } finally { setBusy(false); }
  }, [cart.kitchenId]);
  useFocusEffect(useCallback(() => { load(); }, [load]));

  const items = data?.items || [];
  const cats = useMemo(() => ['All', ...new Set(items.map((i) => i.dish.category))], [data]);
  const shown = items.filter((i) => (cat === 'All' || i.dish.category === cat) && (!onlyMine || i.fits_preferences)
    && (!q || `${i.dish.name} ${i.dish.description || ''} ${i.dish.ingredients || ''}`.toLowerCase().includes(q.toLowerCase())));
  const sections = [...new Set(shown.map((i) => i.dish.category))].map((c) => ({ title: c, items: shown.filter((i) => i.dish.category === c) }));
  const d = data ? new Date(data.date + 'T00:00:00') : null;
  const when = d ? d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }) : '';
  const cutoff = items[0]?.cutoff || '10:45';

  return (
    <Screen refreshing={busy} onRefresh={load} gap={14}>
      <Rise index={0} style={{ flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between' }}>
        <View style={{ flex: 1 }}>
          <Text accessibilityRole="header" style={{ fontSize: 30, fontWeight: '800', color: C.ink, letterSpacing: -0.5 }}>Menu</Text>
          {data ? <Muted style={{ fontWeight: '600' }}>{data.date === localToday() ? '' : 'Tomorrow · '}{when} · Lunch</Muted> : null}
        </View>
        <Tag label={`Order by ${cutoff}`} style={{ marginTop: 8, fontSize: 12 }} />
      </Rise>

      <Rise index={1}>
        <Glass style={{ flexDirection: 'row', alignItems: 'center', gap: 10, borderRadius: 999, paddingLeft: 16, paddingRight: 6, minHeight: 52 }}>
          <Icon name="search" size={18} color={C.muted} />
          <TextInput value={q} onChangeText={setQ} placeholder="Search dishes or ingredients" placeholderTextColor={C.muted} accessibilityLabel="Search dishes or ingredients" style={{ flex: 1, fontSize: 15, color: C.ink, minHeight: 48 }} returnKeyType="search" />
          <Pressable onPress={() => setOnlyMine(!onlyMine)} accessibilityRole="switch" accessibilityState={{ checked: onlyMine }} accessibilityLabel="Show only dishes that suit me" style={{ width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center', backgroundColor: onlyMine ? C.teal : 'rgba(18,48,42,0.06)' }}>
            <Icon name="filter" size={18} color={onlyMine ? '#fff' : C.ink} />
          </Pressable>
        </Glass>
      </Rise>
      {onlyMine ? <Muted style={{ marginTop: -6 }}>Showing dishes that suit your food preferences.</Muted> : null}

      <Rise index={2}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginHorizontal: -20 }} contentContainerStyle={{ gap: 8, paddingHorizontal: 20 }}>
          {cats.map((c) => (
            <Pressable key={c} onPress={() => setCat(c)} accessibilityRole="button" accessibilityState={{ selected: cat === c }} style={{ height: 42, paddingHorizontal: 16, borderRadius: 999, justifyContent: 'center', backgroundColor: cat === c ? C.teal : 'rgba(255,255,255,0.7)', borderWidth: cat === c ? 0 : 1, borderColor: 'rgba(18,48,42,0.08)' }}>
              <Text style={{ fontWeight: '800', fontSize: 14, color: cat === c ? '#fff' : C.ink }}>{c}</Text>
            </Pressable>
          ))}
        </ScrollView>
      </Rise>
      <View style={{ flexDirection: 'row', gap: 14, flexWrap: 'wrap' }} accessibilityLabel="Colour key: grains, protein, vegetables, dairy">
        {[['Grains', C.grains], ['Protein', C.protein], ['Vegetables', C.veg], ['Dairy', C.dairy]].map(([l, c]) => <View key={l} style={{ flexDirection: 'row', alignItems: 'center', gap: 5 }}><View style={{ width: 9, height: 9, borderRadius: 3, backgroundColor: c }} /><Text style={{ fontSize: 12, color: C.muted, fontWeight: '600' }}>{l}</Text></View>)}
      </View>

      {error ? <Glass style={{ padding: 16, borderRadius: 22 }}><Muted>{error}</Muted></Glass> : null}

      {sections.map((sec, si) => (
        <View key={sec.title} style={{ gap: 12 }}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'baseline', marginTop: 6 }}>
            <Text accessibilityRole="header" style={{ fontSize: 20, fontWeight: '800', color: C.ink }}>{sec.title}</Text>
            <Text style={{ fontSize: 12, fontWeight: '700', color: C.muted }}>{sec.items.length} item{sec.items.length === 1 ? '' : 's'}</Text>
          </View>
          {sec.items.map((it, i) => {
            const off = it.status !== 'open';
            return (
              <Rise key={it.daily_menu_id} index={Math.min(si * 3 + i, 8)}>
                <Pressable onPress={() => navigation.navigate('Dish', { item: it })} accessibilityRole="button" accessibilityLabel={`${it.dish.name}, ${rupees(it.price_paise)}${off ? ', sold out' : ''}`}>
                  <Glass style={{ borderRadius: 24, padding: 14, flexDirection: 'row', gap: 12, alignItems: 'center', opacity: off ? 0.6 : 1 }}>
                    <Plate uri={it.dish.image_url} size={84} {...plateColors(it.dish)} />
                    <View style={{ flex: 1, gap: 5 }}>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}><DietMark diet={it.dish.diet} /><Text style={{ flex: 1, fontWeight: '800', fontSize: 16, color: C.ink }} numberOfLines={1}>{it.dish.name}</Text></View>
                      {it.dish.description ? <Text numberOfLines={1} style={{ fontSize: 13, color: C.muted }}>{it.dish.description}</Text> : null}
                      <BalanceBar balance={it.dish.balance} width={150} height={5} />
                      {it.allergy_conflict ? <Text style={{ fontSize: 12, color: C.badInk, fontWeight: '700' }}>Contains your allergen</Text> : it.fits_preferences ? <Text style={{ fontSize: 12, color: C.teal, fontWeight: '700' }}>Suits your preferences</Text> : null}
                      <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 2 }}>
                        <Text style={{ fontSize: 16, fontWeight: '800', color: C.ink }}>{rupees(it.price_paise)}<Text style={{ fontSize: 12, color: C.muted, fontWeight: '700' }}>{it.dish.kcal ? `  ·  ${it.dish.kcal} kcal` : ''}{!off && it.available <= 5 ? `  ·  ${it.available} left` : ''}</Text></Text>
                        <AddButton item={it} />
                      </View>
                    </View>
                  </Glass>
                </Pressable>
              </Rise>
            );
          })}
        </View>
      ))}
      {data && !shown.length ? <Muted style={{ textAlign: 'center', marginTop: 20 }}>No dishes match. Try another category or search.</Muted> : null}
    </Screen>
  );
}
