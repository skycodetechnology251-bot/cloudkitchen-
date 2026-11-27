import React, { useState } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { WebView } from 'react-native-webview';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api, BASE } from '../shared/api';
import { Btn, Glass, H2, Muted } from '../shared/ui';
import { C } from '../shared/theme';
import { useAuth } from '../auth';

/** Razorpay Checkout runs in a WebView on our own /pay page; the result comes back over postMessage. */
export default function PayScreen({ navigation, route }) {
  const { payment, orderId, purpose } = route.params;
  const insets = useSafeAreaInsets();
  const { refresh } = useAuth();
  const [state, setState] = useState('paying');
  const [msg, setMsg] = useState('');

  const qs = new URLSearchParams({ key: payment.key_id, order: payment.razorpay_order_id, amount: String(payment.amount_paise), desc: payment.description || '', name: payment.prefill?.name || '', contact: payment.prefill?.contact || '', email: payment.prefill?.email || '' });

  const done = () => {
    refresh();
    if (purpose === 'order') navigation.replace('Order', { id: orderId, fresh: true });
    else navigation.goBack();
  };

  const onMessage = async (e) => {
    let m; try { m = JSON.parse(e.nativeEvent.data); } catch (x) { return; }
    if (m.type === 'success') {
      setState('verifying');
      try { await api('/payments/razorpay/verify', { method: 'POST', body: m }); setState('ok'); setTimeout(done, 900); }
      catch (err) { setState('pending'); setMsg('We are confirming your payment with the bank. It will update in a minute.'); }
    } else if (m.type === 'failed') { setState('failed'); setMsg(m.reason || 'Payment failed. Nothing was charged.'); }
    else if (m.type === 'dismissed') { setState('failed'); setMsg('Payment was cancelled.'); }
  };

  return (
    <View style={{ flex: 1, backgroundColor: C.bg, paddingTop: insets.top }}>
      {state === 'paying' ? (
        <WebView source={{ uri: `${BASE}/pay?${qs.toString()}` }} onMessage={onMessage} startInLoadingState javaScriptEnabled
          renderLoading={() => <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center' }}><ActivityIndicator color={C.teal} /></View>}
          setSupportMultipleWindows={false} originWhitelist={['https://*', 'upi://*', 'intent://*']} />
      ) : (
        <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 28 }}>
          <Glass strong style={{ padding: 24, gap: 12, alignItems: 'center', alignSelf: 'stretch' }}>
            {state === 'verifying' ? <ActivityIndicator color={C.teal} /> : <Text style={{ fontSize: 44 }}>{state === 'ok' ? '✅' : state === 'pending' ? '⏳' : '⚠️'}</Text>}
            <H2>{state === 'verifying' ? 'Confirming payment…' : state === 'ok' ? 'Payment successful' : state === 'pending' ? 'Payment processing' : 'Payment not completed'}</H2>
            {msg ? <Muted style={{ textAlign: 'center' }}>{msg}</Muted> : null}
            {state === 'failed' ? <Btn title="Try again" onPress={() => setState('paying')} style={{ alignSelf: 'stretch' }} /> : null}
            {state === 'failed' || state === 'pending' ? <Btn kind="ghost" title={purpose === 'order' ? 'View order' : 'Back to wallet'} onPress={done} style={{ alignSelf: 'stretch' }} /> : null}
          </Glass>
        </View>
      )}
    </View>
  );
}
