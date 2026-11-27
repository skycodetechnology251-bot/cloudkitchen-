import React, { useCallback, useState } from 'react';
import { Pressable, Text, TextInput, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { api, rupees } from '../shared/api';
import { Btn, Glass, Muted, Progress, Rise, Segmented, Tag, Toggle } from '../shared/ui';
import { C } from '../shared/theme';
import { Header, Screen } from './_Screen';

const TX = { topup: 'Top-up', order: 'Lunch order', refund: 'Refund', adjustment: 'Adjustment', cashback: 'Cashback' };
const AVG = 14000; // average lunch with delivery, in paise

export default function WalletScreen({ navigation }) {
  const [w, setW] = useState(null);
  const [amount, setAmount] = useState(100000);
  const [custom, setCustom] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const load = useCallback(async () => { try { setW(await api('/wallet')); } catch (e) {} }, []);
  useFocusEffect(useCallback(() => { load(); }, [load]));
  const set = async (body) => { try { setW(await api('/wallet', { method: 'PATCH', body })); } catch (e) { setError(e.message); } };

  const paise = custom ? Math.round(Number(custom) * 100) : amount;
  const topup = async () => {
    if (!paise || paise < 10000) { setError('Minimum top-up is ₹100.'); return; }
    try { setBusy(true); setError(''); const r = await api('/wallet/topup', { method: 'POST', body: { amount_paise: paise } }); navigation.navigate('Pay', { payment: r.payment, purpose: 'wallet' }); }
    catch (e) { setError(e.message); } finally { setBusy(false); }
  };
  if (!w) return <Screen><Header title="Wallet" onBack={() => navigation.goBack()} /></Screen>;
  const notEnough = w.balance_paise < AVG;

  return (
    <Screen onRefresh={load}>
      <Header title="Wallet" onBack={() => navigation.goBack()} right={<Tag label={`${w.points.toLocaleString('en-IN')} pts`} bg="rgba(255,255,255,0.85)" color={C.ink} style={{ fontSize: 12 }} />} />

      <Rise index={0}>
        <View style={{ backgroundColor: C.teal, borderRadius: 30, padding: 20, gap: 6, overflow: 'hidden' }}>
          <View style={{ position: 'absolute', right: -50, top: -60, width: 180, height: 180, borderRadius: 90, backgroundColor: 'rgba(191,232,214,0.18)' }} />
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={{ color: '#CFE6DF', fontWeight: '700' }}>Balance</Text>
            {w.low ? <Tag label="Low balance" bg={C.amber} color={C.ink} /> : null}
          </View>
          <Text accessibilityLabel={`Balance ${rupees(w.balance_paise, 2)}`} style={{ color: '#fff', fontSize: 42, fontWeight: '800', letterSpacing: -1 }}>{rupees(w.balance_paise, 2)}</Text>
          <Text style={{ color: '#fff', fontWeight: '700', fontSize: 15 }}>{notEnough ? "Not enough for today's lunch" : `Covers about ${w.lunches_covered} lunch${w.lunches_covered === 1 ? '' : 'es'}`}</Text>
          <View style={{ marginTop: 8 }}><Progress value={w.lunches_covered / 10} color={C.amber} track="rgba(255,255,255,0.22)" height={7} /></View>
          <Text style={{ color: '#CFE6DF', fontSize: 12 }}>Bar shows 10 lunches. Lunch averages {rupees(AVG)} with delivery.</Text>
        </View>
      </Rise>

      <Rise index={1}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 12 }}>
          <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>Add money</Text>
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 10 }} accessibilityRole="radiogroup">
            {w.presets_paise.map((p) => {
              const on = !custom && amount === p;
              return (
                <Pressable key={p} onPress={() => { setCustom(''); setAmount(p); }} accessibilityRole="radio" accessibilityState={{ selected: on }}
                  style={{ flexBasis: '47%', flexGrow: 1, borderRadius: 16, padding: 12, borderWidth: on ? 2 : 1, borderColor: on ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: on ? 'rgba(227,241,236,0.95)' : 'rgba(255,255,255,0.85)' }}>
                  <Text style={{ fontSize: 19, fontWeight: '800', color: C.ink }}>{rupees(p)}</Text>
                  <Text style={{ fontSize: 12, color: C.muted }}>≈ {Math.floor(p / AVG)} lunches{p >= 200000 ? ' · +50 pts' : ''}</Text>
                </Pressable>
              );
            })}
          </View>
          <TextInput value={custom} onChangeText={(t) => setCustom(t.replace(/\D/g, '').slice(0, 5))} keyboardType="number-pad" placeholder="Other amount (₹)" placeholderTextColor={C.muted} accessibilityLabel="Other amount in rupees"
            style={{ minHeight: 48, borderRadius: 14, borderWidth: 1, borderColor: custom ? C.teal : 'rgba(18,48,42,0.12)', backgroundColor: 'rgba(255,255,255,0.85)', paddingHorizontal: 14, fontSize: 16, fontWeight: '700', color: C.ink }} />
          <Muted style={{ fontSize: 12 }}>Paid securely by UPI or card through Razorpay. Top-ups of ₹2,000 or more earn 50 bonus points.</Muted>
          {error ? <Text style={{ color: C.badInk, fontWeight: '700' }}>{error}</Text> : null}
          <Btn title={busy ? 'Starting…' : `Add ${rupees(paise || 0)}`} onPress={topup} disabled={busy} />
        </Glass>
      </Rise>

      <Rise index={2}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 8 }}>
          <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>Daily lunch from wallet</Text>
          <Toggle value={w.pay_first} onChange={(v) => set({ pay_first: v })} label="Pay lunch from wallet first" note="When you tap Yes and check out, the wallet pays. Any shortfall goes to UPI." />
          <Text style={{ fontWeight: '800', fontSize: 14, color: C.ink, marginTop: 6 }}>Remind me when balance falls below</Text>
          <Segmented options={[[15000, '₹150'], [30000, '₹300'], [50000, '₹500']]} value={w.low_balance_paise} onChange={(v) => set({ low_balance_paise: v })} />
          <Muted style={{ fontSize: 12 }}>We'll send a push and WhatsApp reminder the morning your balance is low.</Muted>
        </Glass>
      </Rise>

      <Rise index={3}>
        <Glass style={{ borderRadius: 26, padding: 16, gap: 10 }}>
          <Text style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>History</Text>
          {!w.transactions.length ? <Muted>No transactions yet.</Muted> : w.transactions.map((t) => (
            <View key={t.id} style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
              <View style={{ flex: 1 }}><Text style={{ fontWeight: '700', color: C.ink }}>{t.note || TX[t.type] || t.type}</Text><Muted style={{ fontSize: 12 }}>{new Date(t.created_at).toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' })}</Muted></View>
              <Text style={{ fontWeight: '800', color: t.amount_paise >= 0 ? C.green : C.ink }}>{t.amount_paise >= 0 ? '+' : '−'}{rupees(Math.abs(t.amount_paise), 2)}</Text>
            </View>
          ))}
        </Glass>
      </Rise>
    </Screen>
  );
}
