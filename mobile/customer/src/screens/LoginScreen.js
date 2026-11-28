import React, { useEffect, useRef, useState } from 'react';
import { Animated, Dimensions, Easing, Image, KeyboardAvoidingView, Linking, Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
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
    // Photos and headline come from Admin → Branding → Mobile app login screen.
    api('/app/login-screen').then((s) => setBanner(s)).catch(() => {});
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

  const pretty = phone.length > 5 ? phone.slice(0, 5) + ' ' + phone.slice(5) : phone;
  const images = banner?.images || [];
  const H = Math.round(Dimensions.get('window').height * 0.52);
  return (
    <KeyboardAvoidingView style={{ flex: 1, backgroundColor: '#0F3D2A' }} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView keyboardShouldPersistTaps="handled" bounces={false} contentContainerStyle={{ flexGrow: 1 }}>
        {/* Top: admin's photos, slowly changing (Admin → Branding → Mobile app login screen) */}
        <View style={{ height: H + insets.top, overflow: 'hidden', backgroundColor: '#14603A' }}>
          {images.length ? <Slides images={images} zoom={zoom} /> : (
            <Animated.View style={{ position: 'absolute', right: -70, top: insets.top + 40, transform: [{ scale: zoom }] }}><Plate size={340} /></Animated.View>
          )}
          <View style={[StyleSheet.absoluteFill, { backgroundColor: 'rgba(5,30,18,0.25)' }]} />
          <View style={{ position: 'absolute', left: 0, right: 0, bottom: 0, height: H * 0.6, backgroundColor: 'rgba(5,30,18,0.45)' }} />
          <View style={{ position: 'absolute', top: insets.top + 14, left: 22, right: 22, flexDirection: 'row', alignItems: 'center', gap: 10 }}>
            {banner?.logo ? <Image source={{ uri: banner.logo }} style={{ width: 38, height: 38, borderRadius: 11, backgroundColor: '#fff' }} resizeMode="contain" />
              : <View style={{ width: 38, height: 38, borderRadius: 11, backgroundColor: '#fff', alignItems: 'center', justifyContent: 'center' }}><Icon name="bowl" size={22} color="#1F8A4C" /></View>}
            <Text style={{ color: '#fff', fontWeight: '800', fontSize: 20, textShadowColor: 'rgba(0,0,0,0.3)', textShadowRadius: 6 }}>{banner?.brand || 'Daily Table'}</Text>
          </View>
          <View style={{ position: 'absolute', left: 24, right: 24, bottom: 44, gap: 8 }}>
            <Text style={{ alignSelf: 'flex-start', backgroundColor: '#F4B400', color: '#1A2E22', fontWeight: '800', fontSize: 11, letterSpacing: 0.8, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 99, overflow: 'hidden' }}>🌿 FRESH EVERY DAY</Text>
            <Text style={{ color: '#fff', fontWeight: '800', fontSize: 32, lineHeight: 36, textShadowColor: 'rgba(0,0,0,0.35)', textShadowRadius: 8 }}>{banner?.title || 'Home-style meals, delivered fresh'}</Text>
            {banner?.subtitle !== '' ? <Text style={{ color: '#EAF6EF', fontWeight: '600', fontSize: 15, lineHeight: 21 }}>{banner?.subtitle || 'Breakfast, lunch, break-time snacks and dinner from your neighbourhood kitchen.'}</Text> : null}
            {images.length > 1 ? <Dots n={images.length} /> : null}
          </View>
        </View>

        {/* Bottom sheet: phone number */}
        <View style={{ flex: 1, marginTop: -26, backgroundColor: '#fff', borderTopLeftRadius: 30, borderTopRightRadius: 30, paddingHorizontal: 24, paddingTop: 26, paddingBottom: 30 + insets.bottom, gap: 14 }}>
          <View><Text style={{ fontSize: 26, fontWeight: '800', color: C.ink }}>Welcome 👋</Text><Muted style={{ fontSize: 15, marginTop: 2 }}>Enter your mobile number to log in or sign up.</Muted></View>
          <View style={{ flexDirection: 'row', alignItems: 'stretch', minHeight: 60, borderRadius: 18, borderWidth: 2, borderColor: error ? C.badInk : focus ? '#1F8A4C' : '#E1E8E5', backgroundColor: '#F7FAF8', overflow: 'hidden' }}>
            <View style={{ paddingHorizontal: 16, justifyContent: 'center', flexDirection: 'row', alignItems: 'center', gap: 6, borderRightWidth: 1, borderRightColor: '#E1E8E5' }}><Text style={{ fontSize: 18 }}>🇮🇳</Text><Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }}>+91</Text></View>
            <TextInput value={pretty} onChangeText={(t) => setPhone(t.replace(/\D/g, '').slice(0, 10))} onFocus={() => setFocus(true)} onBlur={() => setFocus(false)} onSubmitEditing={send}
              keyboardType="number-pad" placeholder="98410 23456" placeholderTextColor="#9AACA5" autoComplete="tel" textContentType="telephoneNumber" accessibilityLabel="Mobile number" returnKeyType="done"
              style={{ flex: 1, paddingHorizontal: 16, fontSize: 20, fontWeight: '700', letterSpacing: 1, color: C.ink }} />
          </View>
          <Text style={{ fontSize: 13, color: error ? C.badInk : C.muted, fontWeight: error ? '700' : '500', marginTop: -6 }}>{error || "We'll send a 6-digit code by SMS."}</Text>
          <Btn title={busy ? 'Sending…' : 'Get OTP'} onPress={send} disabled={busy || phone.length < 10} accent="#1F8A4C" />
          {googleReady ? (
            <View style={{ gap: 14 }}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}><View style={{ flex: 1, height: 1, backgroundColor: '#E1E8E5' }} /><Muted>or</Muted><View style={{ flex: 1, height: 1, backgroundColor: '#E1E8E5' }} /></View>
              <GoogleButton disabled={busy} onIdToken={googleSignIn} />
            </View>
          ) : null}
          <View style={{ flexDirection: 'row', justifyContent: 'center', gap: 18, marginTop: 4 }}>
            {[['🌅', 'Breakfast'], ['🍛', 'Lunch'], ['☕', 'Snacks'], ['🌙', 'Dinner']].map(([e, l]) => (
              <View key={l} style={{ alignItems: 'center', gap: 2 }}><Text style={{ fontSize: 22 }}>{e}</Text><Text style={{ fontSize: 11, fontWeight: '700', color: C.muted }}>{l}</Text></View>
            ))}
          </View>
          <Text style={{ textAlign: 'center', fontSize: 12, color: C.muted, lineHeight: 18 }}>
            By continuing you agree to our{' '}
            <Text style={{ textDecorationLine: 'underline', color: C.ink }} onPress={() => Linking.openURL(BASE + '/terms')}>Terms</Text> and{' '}
            <Text style={{ textDecorationLine: 'underline', color: C.ink }} onPress={() => Linking.openURL(BASE + '/privacy')}>Privacy policy</Text>.
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

/** Admin's photos, one after another with a soft crossfade and a slow zoom. */
function Slides({ images, zoom }) {
  const [i, setI] = useState(0);
  const fades = useRef(images.map((_, k) => new Animated.Value(k === 0 ? 1 : 0))).current;
  useEffect(() => {
    if (images.length < 2) return undefined;
    const t = setInterval(() => {
      setI((cur) => {
        const next = (cur + 1) % images.length;
        Animated.parallel([
          Animated.timing(fades[cur], { toValue: 0, duration: 1200, useNativeDriver: true }),
          Animated.timing(fades[next], { toValue: 1, duration: 1200, useNativeDriver: true }),
        ]).start();
        return next;
      });
    }, 5000);
    return () => clearInterval(t);
  }, [images.length]);
  return images.map((uri, k) => (
    <Animated.Image key={uri} source={{ uri }} resizeMode="cover" accessibilityIgnoresInvertColors
      style={[StyleSheet.absoluteFill, { opacity: fades[k] || 0, transform: [{ scale: zoom }] }]} />
  ));
}

function Dots({ n }) {
  return <View style={{ flexDirection: 'row', gap: 6, marginTop: 6 }}>{Array.from({ length: n }, (_, k) => <View key={k} style={{ width: 7, height: 7, borderRadius: 4, backgroundColor: 'rgba(255,255,255,0.7)' }} />)}</View>;
}
