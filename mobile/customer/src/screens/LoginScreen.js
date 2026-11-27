import React, { useEffect, useRef, useState } from 'react';
import { Animated, Easing, Image, KeyboardAvoidingView, Linking, Platform, Pressable, ScrollView, Text, TextInput, View } from 'react-native';
import * as Google from 'expo-auth-session/providers/google';
import * as WebBrowser from 'expo-web-browser';
import Constants from 'expo-constants';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { api, BASE } from '../shared/api';
import { Backdrop, Btn, Glass, Muted, Rise } from '../shared/ui';
import Icon from '../shared/Icons';
import Plate from '../shared/Plate';
import { C } from '../shared/theme';
import { useAuth } from '../auth';

WebBrowser.maybeCompleteAuthSession();
const extra = Constants.expoConfig?.extra || {};
// Google sign-in needs the client ID for THIS platform (Android: android ID, iPhone: iOS ID). Without it the
// Google library throws while the screen is drawn, so the button (and its hook) only exist when the ID is set.
const platformClientId = Platform.OS === 'android' ? extra.googleAndroidClientId : Platform.OS === 'ios' ? extra.googleIosClientId : extra.googleWebClientId;
const googleReady = !!platformClientId;

/** "Continue with Google". Rendered only when this platform's client ID is configured. */
function GoogleButton({ disabled, onIdToken }) {
  const [request, response, promptAsync] = Google.useIdTokenAuthRequest({
    androidClientId: extra.googleAndroidClientId || undefined,
    iosClientId: extra.googleIosClientId || undefined,
    webClientId: extra.googleWebClientId || undefined,
  });
  useEffect(() => { if (response?.type === 'success') onIdToken(response.params.id_token); }, [response]);
  return (
    <Pressable accessibilityRole="button" disabled={!request || disabled} onPress={() => promptAsync().catch(() => {})}>
      <Glass style={{ minHeight: 54, borderRadius: 999, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 10 }}><Icon name="globe" /><Text style={{ fontWeight: '800', fontSize: 16, color: C.ink }}>Continue with Google</Text></Glass>
    </Pressable>
  );
}

export default function LoginScreen({ navigation }) {
  const insets = useSafeAreaInsets();
  const { signIn } = useAuth();
  const [phone, setPhone] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState(null);
  const [googleLink, setGoogleLink] = useState(null);
  const [focus, setFocus] = useState(false);
  const zoom = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    api('/banners', { query: { placement: 'login' } }).then((b) => setBanner(b[0] || null)).catch(() => {});
    Animated.loop(Animated.sequence([
      Animated.timing(zoom, { toValue: 1.08, duration: 9000, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
      Animated.timing(zoom, { toValue: 1, duration: 9000, easing: Easing.inOut(Easing.sin), useNativeDriver: true }),
    ])).start();
  }, [zoom]);

  const googleSignIn = async (idToken) => {
    try {
      setBusy(true);
      const r = await api('/auth/google', { method: 'POST', body: { id_token: idToken, device: Platform.OS } });
      if (r.needs_phone) { setGoogleLink(r.google_link_token); setError('Google account linked. Now verify your mobile number to finish.'); }
      else await signIn(r);
    } catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  const send = async () => {
    setError('');
    if (!/^[6-9]\d{9}$/.test(phone)) { setError('Enter a 10-digit Indian mobile number.'); return; }
    try {
      setBusy(true);
      await api('/auth/otp/request', { method: 'POST', body: { phone } });
      navigation.navigate('Otp', { phone, googleLink });
    } catch (e) { setError(e.message); } finally { setBusy(false); }
  };

  const d = banner?.design || {};
  const pretty = phone.length > 5 ? phone.slice(0, 5) + ' ' + phone.slice(5) : phone;
  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: C.bg }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Backdrop />
      <ScrollView keyboardShouldPersistTaps="handled" contentContainerStyle={{ paddingBottom: 40 + insets.bottom }}>
        {/* Admin-editable login banner (Admin → Banners → placement "login") */}
        <View accessibilityRole="image" accessibilityLabel={banner?.title || 'Home-style lunch, by 1 PM'} style={{ height: 360 + insets.top, backgroundColor: d.bg || C.teal, borderBottomLeftRadius: 36, borderBottomRightRadius: 36, overflow: 'hidden', paddingTop: insets.top + 14, paddingHorizontal: 24 }}>
          <Animated.View style={{ position: 'absolute', right: -90, top: insets.top + 60, transform: [{ scale: zoom }] }}>
            {banner?.image_url ? <Image source={{ uri: banner.image_url }} style={{ width: 300, height: 300, borderRadius: 150 }} /> : <Plate size={320} />}
          </Animated.View>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
            <View style={{ width: 32, height: 32, borderRadius: 9, backgroundColor: 'rgba(255,255,255,0.16)', alignItems: 'center', justifyContent: 'center' }}><Icon name="bowl" size={18} color={C.mint} /></View>
            <Text style={{ color: '#fff', fontWeight: '800', fontSize: 19 }}>Daily Table</Text>
          </View>
          <View style={{ position: 'absolute', left: 24, bottom: 30, gap: 8, maxWidth: 230 }}>
            <Text style={{ alignSelf: 'flex-start', backgroundColor: d.accent || 'rgba(255,255,255,0.92)', color: C.ink, fontWeight: '800', fontSize: 11, letterSpacing: 0.8, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 99, overflow: 'hidden' }}>{banner?.tag || 'FRESH EVERY DAY'}</Text>
            <Text style={{ color: d.ink || '#fff', fontWeight: '800', fontSize: 30, lineHeight: 32 }}>{banner?.title || 'Home-style lunch, by 1 PM'}</Text>
            <Text style={{ color: d.ink || '#E3F1EC', opacity: 0.9, fontWeight: '700', fontSize: 14 }}>{banner?.subtitle || 'Cooked in your neighbourhood kitchen'}</Text>
          </View>
        </View>

        <View style={{ padding: 24, gap: 14 }}>
          <Rise index={0}><Text style={{ fontSize: 30, fontWeight: '800', color: C.ink }}>Welcome</Text><Muted style={{ fontSize: 15 }}>Log in or sign up with your mobile number.</Muted></Rise>
          <Rise index={1} style={{ gap: 6 }}>
            <Text style={{ fontSize: 13, fontWeight: '800', color: C.ink }}>Mobile number</Text>
            <View style={{ flexDirection: 'row', alignItems: 'stretch', minHeight: 58, borderRadius: 16, borderWidth: 2, borderColor: error ? C.badInk : focus ? C.teal : 'rgba(18,48,42,0.14)', backgroundColor: 'rgba(255,255,255,0.8)', overflow: 'hidden' }}>
              <View style={{ paddingHorizontal: 14, justifyContent: 'center', borderRightWidth: 1, borderRightColor: 'rgba(18,48,42,0.12)' }}><Text style={{ fontSize: 17, fontWeight: '800', color: C.ink }}>+91</Text></View>
              <TextInput value={pretty} onChangeText={(t) => setPhone(t.replace(/\D/g, '').slice(0, 10))} onFocus={() => setFocus(true)} onBlur={() => setFocus(false)}
                keyboardType="number-pad" placeholder="98410 23456" placeholderTextColor={C.muted} autoComplete="tel" textContentType="telephoneNumber" accessibilityLabel="Mobile number"
                style={{ flex: 1, paddingHorizontal: 14, fontSize: 18, fontWeight: '700', letterSpacing: 1, color: C.ink }} />
            </View>
            <Text style={{ fontSize: 12, color: error ? C.badInk : C.muted, fontWeight: error ? '700' : '500' }}>{error || "We'll send a 6-digit code by SMS."}</Text>
          </Rise>
          <Rise index={2}><Btn title={busy ? 'Sending…' : 'Send OTP'} onPress={send} disabled={busy} /></Rise>
          {googleReady ? (
            <Rise index={3} style={{ gap: 14 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}><View style={{ flex: 1, height: 1, backgroundColor: '#D5E2DD' }} /><Muted>or</Muted><View style={{ flex: 1, height: 1, backgroundColor: '#D5E2DD' }} /></View>
              <GoogleButton disabled={busy} onIdToken={googleSignIn} />
            </Rise>
          ) : null}
          <Text style={{ textAlign: 'center', fontSize: 12, color: C.muted, lineHeight: 18, marginTop: 6 }}>
            New here? {googleReady ? 'Google or OTP' : 'OTP'} creates your account. By continuing you agree to our{' '}
            <Text style={{ textDecorationLine: 'underline', color: C.ink }} onPress={() => Linking.openURL(BASE + '/terms')}>Terms</Text> and{' '}
            <Text style={{ textDecorationLine: 'underline', color: C.ink }} onPress={() => Linking.openURL(BASE + '/privacy')}>Privacy policy</Text>.
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
