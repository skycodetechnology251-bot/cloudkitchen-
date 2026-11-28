import React, { useEffect, useRef, useState } from 'react';
import { Animated, Dimensions, Easing, Image, Keyboard, Linking, Platform, Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
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
  const { signIn, skip } = useAuth();
  const [phone, setPhone] = useState('');
  const [kb, setKb] = useState(false);
  const [kbPad, setKbPad] = useState(0);
  const scrollRef = useRef(null);
  const rootRef = useRef(null);
  const sheetY = useRef(0);
  const fieldY = useRef(0);
  // Scroll so the "Welcome" heading and number box sit just above the keyboard.
  const toField = () => scrollRef.current?.scrollTo({ y: Math.max(0, sheetY.current + fieldY.current - 70), animated: true });
  useEffect(() => {
    // Keep the number box above the keyboard on every phone. Newer Android (edge-to-edge) does not
    // shrink the screen for the keyboard, older ones do: measure how much of the screen the keyboard
    // really covers and add exactly that much space under the form, then scroll to it.
    const onShow = (e) => {
      const kbH = e?.endCoordinates?.height || 0;
      setKb(true);
      const fit = () => rootRef.current?.measureInWindow((x, y, w, h) => {
        const screenH = Dimensions.get('window').height;
        const covered = Math.max(0, kbH - Math.max(0, screenH - (y + h)));
        setKbPad(covered);
        setTimeout(toField, 60);
      });
      setTimeout(fit, Platform.OS === 'android' ? 120 : 0);
    };
    const onHide = () => { setKb(false); setKbPad(0); };
    const show = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow', onShow);
    const hide = Keyboard.addListener(Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide', onHide);
    return () => { show.remove(); hide.remove(); };
  }, []);
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  const [banner, setBanner] = useState(null);
  const [googleLink, setGoogleLink] = useState(null);
  const [focus, setFocus] = useState(false);
  // When the site has no SMS provider set up, OTP codes can't be sent: use email + password instead.
  const smsOn = banner ? banner.sms !== false : true;
  const [mode, setMode] = useState(null);
  const useEmail = mode ? mode === 'email' : !smsOn;
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
  // While typing, the photo shrinks to a strip so the number box stays visible above the keyboard.
  const H = kb ? 120 : Math.round(Dimensions.get('window').height * 0.52);
  return (
    <View ref={rootRef} collapsable={false} style={{ flex: 1, backgroundColor: '#fff' }}>
      <ScrollView ref={scrollRef} keyboardShouldPersistTaps="handled" bounces={false} contentContainerStyle={{ flexGrow: 1, paddingBottom: kbPad }}>
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
            <Text style={{ flex: 1, color: '#fff', fontWeight: '800', fontSize: 20, textShadowColor: 'rgba(0,0,0,0.3)', textShadowRadius: 6 }} numberOfLines={1}>{banner?.brand || 'Daily Table'}</Text>
            {/* Look around first; log in later (at checkout, orders or profile). */}
            <Pressable onPress={skip} accessibilityRole="button" accessibilityLabel="Skip for now and look around" hitSlop={10}
              style={{ paddingHorizontal: 16, height: 36, borderRadius: 999, backgroundColor: 'rgba(255,255,255,0.92)', justifyContent: 'center' }}>
              <Text style={{ color: '#14603A', fontWeight: '800', fontSize: 14 }}>Skip</Text>
            </Pressable>
          </View>
          {kb ? null : (
          <View style={{ position: 'absolute', left: 24, right: 24, bottom: 44, gap: 8 }}>
              <Text style={{ alignSelf: 'flex-start', backgroundColor: '#F4B400', color: '#1A2E22', fontWeight: '800', fontSize: 11, letterSpacing: 0.8, paddingHorizontal: 10, paddingVertical: 5, borderRadius: 99, overflow: 'hidden' }}>🌿 FRESH EVERY DAY</Text>
              <Text style={{ color: '#fff', fontWeight: '800', fontSize: 32, lineHeight: 36, textShadowColor: 'rgba(0,0,0,0.35)', textShadowRadius: 8 }}>{banner?.title || 'Home-style meals, delivered fresh'}</Text>
              {banner?.subtitle !== '' ? <Text style={{ color: '#EAF6EF', fontWeight: '600', fontSize: 15, lineHeight: 21 }}>{banner?.subtitle || 'Breakfast, lunch, break-time snacks and dinner from your neighbourhood kitchen.'}</Text> : null}
              {images.length > 1 ? <Dots n={images.length} /> : null}
            </View>
          )}
        </View>

        {/* Bottom sheet: phone number */}
        <View onLayout={(e) => { sheetY.current = e.nativeEvent.layout.y; }} style={{ flex: 1, marginTop: -26, backgroundColor: '#fff', borderTopLeftRadius: 30, borderTopRightRadius: 30, paddingHorizontal: 24, paddingTop: 26, paddingBottom: 30 + insets.bottom, gap: 14 }}>
          {useEmail ? (
            <EmailForm signIn={signIn} smsOn={smsOn} onUsePhone={() => { setError(''); setMode('phone'); }} />
          ) : (
            <>
            <View><Text style={{ fontSize: 26, fontWeight: '800', color: C.ink }}>Welcome 👋</Text><Muted style={{ fontSize: 15, marginTop: 2 }}>Enter your mobile number to log in or sign up.</Muted></View>
            <View onLayout={(e) => { fieldY.current = e.nativeEvent.layout.y; }} style={{ flexDirection: 'row', alignItems: 'stretch', minHeight: 60, borderRadius: 18, borderWidth: 2, borderColor: error ? C.badInk : focus ? '#1F8A4C' : '#E1E8E5', backgroundColor: '#F7FAF8', overflow: 'hidden' }}>
              <View style={{ paddingHorizontal: 16, justifyContent: 'center', flexDirection: 'row', alignItems: 'center', gap: 6, borderRightWidth: 1, borderRightColor: '#E1E8E5' }}><Text style={{ fontSize: 18 }}>🇮🇳</Text><Text style={{ fontSize: 18, fontWeight: '800', color: C.ink }}>+91</Text></View>
              <TextInput value={pretty} onChangeText={(t) => setPhone(t.replace(/\D/g, '').slice(0, 10))} onFocus={() => setFocus(true)} onBlur={() => setFocus(false)} onSubmitEditing={send}
                keyboardType="number-pad" placeholder="98410 23456" placeholderTextColor="#9AACA5" autoComplete="tel" textContentType="telephoneNumber" accessibilityLabel="Mobile number" returnKeyType="done"
                style={{ flex: 1, paddingHorizontal: 16, fontSize: 20, fontWeight: '700', letterSpacing: 1, color: C.ink }} />
            </View>
            <Text style={{ fontSize: 13, color: error ? C.badInk : C.muted, fontWeight: error ? '700' : '500', marginTop: -6 }}>{error || "We'll send a 6-digit code by SMS."}</Text>
            <Btn title={busy ? 'Sending…' : 'Get OTP'} onPress={send} disabled={busy || phone.length < 10} accent="#1F8A4C" />
              <Pressable onPress={() => { setError(''); setMode('email'); }} accessibilityRole="button" hitSlop={8} style={{ alignSelf: 'center', paddingVertical: 4 }}>
                <Text style={{ color: '#14603A', fontWeight: '700', fontSize: 14 }}>Use email and password instead</Text>
              </Pressable>
            </>
          )}
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
    </View>
  );
}

const field = { minHeight: 56, borderRadius: 16, borderWidth: 2, borderColor: '#E1E8E5', backgroundColor: '#F7FAF8', paddingHorizontal: 16, fontSize: 17, color: C.ink };

/** Sign up / log in with email and password (no SMS needed). */
function EmailForm({ signIn, smsOn, onUsePhone }) {
  const [tab, setTab] = useState('signup');
  const [f, setF] = useState({ name: '', email: '', phone: '', password: '', password2: '' });
  const [err, setErr] = useState('');
  const [busy, setBusy] = useState(false);
  const [show, setShow] = useState(false);
  const set = (k) => (v) => setF((o) => ({ ...o, [k]: k === 'phone' ? v.replace(/\D/g, '').slice(0, 10) : v }));
  const signup = tab === 'signup';
  const submit = async () => {
    setErr('');
    const email = f.email.trim();
    if (signup && f.name.trim().length < 2) return setErr('Please enter your name.');
    if (!/^\S+@\S+\.\S+$/.test(email)) return setErr('Please enter a valid email.');
    if (signup && !/^[6-9]\d{9}$/.test(f.phone)) return setErr('Enter your 10-digit mobile number (for delivery).');
    if (f.password.length < (signup ? 8 : 1)) return setErr(signup ? 'Use at least 8 characters for your password.' : 'Enter your password.');
    if (signup && f.password !== f.password2) return setErr('The two passwords do not match.');
    try {
      setBusy(true);
      const r = signup
        ? await api('/auth/email/register', { method: 'POST', body: { name: f.name.trim(), email, phone: f.phone, password: f.password, password_confirmation: f.password2, device: Platform.OS } })
        : await api('/auth/email/login', { method: 'POST', body: { email, password: f.password, device: Platform.OS } });
      await signIn(r);
    } catch (e) { setErr(e.message); } finally { setBusy(false); }
  };
  return (
    <View style={{ gap: 12 }}>
      <View><Text style={{ fontSize: 26, fontWeight: '800', color: C.ink }}>{signup ? 'Create your account' : 'Welcome back 👋'}</Text>
        <Muted style={{ fontSize: 15, marginTop: 2 }}>{signup ? 'Sign up with your email and a password.' : 'Log in with your email and password.'}</Muted></View>
      <View style={{ flexDirection: 'row', backgroundColor: '#EEF4F1', borderRadius: 999, padding: 4 }}>
        {[['signup', 'Sign up'], ['login', 'Log in']].map(([k, l]) => (
          <Pressable key={k} onPress={() => { setTab(k); setErr(''); }} accessibilityRole="tab" accessibilityState={{ selected: tab === k }}
            style={{ flex: 1, minHeight: 42, borderRadius: 999, alignItems: 'center', justifyContent: 'center', backgroundColor: tab === k ? '#1F8A4C' : 'transparent' }}>
            <Text style={{ fontWeight: '800', color: tab === k ? '#fff' : C.ink }}>{l}</Text>
          </Pressable>
        ))}
      </View>
      {signup ? <TextInput value={f.name} onChangeText={set('name')} placeholder="Your name" placeholderTextColor="#9AACA5" autoComplete="name" textContentType="name" accessibilityLabel="Your name" style={field} /> : null}
      <TextInput value={f.email} onChangeText={set('email')} placeholder="Email" placeholderTextColor="#9AACA5" keyboardType="email-address" autoCapitalize="none" autoCorrect={false} autoComplete="email" textContentType="emailAddress" accessibilityLabel="Email" style={field} />
      {signup ? <TextInput value={f.phone} onChangeText={set('phone')} placeholder="Mobile number (for delivery)" placeholderTextColor="#9AACA5" keyboardType="number-pad" autoComplete="tel" textContentType="telephoneNumber" accessibilityLabel="Mobile number" style={field} /> : null}
      <View>
        <TextInput value={f.password} onChangeText={set('password')} placeholder={signup ? 'Password (8 or more characters)' : 'Password'} placeholderTextColor="#9AACA5" secureTextEntry={!show} autoCapitalize="none" autoComplete={signup ? 'password-new' : 'password'} textContentType={signup ? 'newPassword' : 'password'} accessibilityLabel="Password" style={[field, { paddingRight: 70 }]} onSubmitEditing={signup ? undefined : submit} />
        <Pressable onPress={() => setShow((v) => !v)} hitSlop={8} accessibilityRole="button" accessibilityLabel={show ? 'Hide password' : 'Show password'} style={{ position: 'absolute', right: 14, top: 0, bottom: 0, justifyContent: 'center' }}>
          <Text style={{ color: '#14603A', fontWeight: '700' }}>{show ? 'Hide' : 'Show'}</Text>
        </Pressable>
      </View>
      {signup ? <TextInput value={f.password2} onChangeText={set('password2')} placeholder="Type the password again" placeholderTextColor="#9AACA5" secureTextEntry={!show} autoCapitalize="none" accessibilityLabel="Confirm password" style={field} onSubmitEditing={submit} /> : null}
      {err ? <Text style={{ fontSize: 14, color: C.badInk, fontWeight: '700' }}>{err}</Text> : null}
      <Btn title={busy ? 'Please wait…' : signup ? 'Create account' : 'Log in'} onPress={submit} disabled={busy} accent="#1F8A4C" />
      {smsOn ? (
        <Pressable onPress={onUsePhone} accessibilityRole="button" hitSlop={8} style={{ alignSelf: 'center', paddingVertical: 4 }}>
          <Text style={{ color: '#14603A', fontWeight: '700', fontSize: 14 }}>Use mobile number (OTP) instead</Text>
        </Pressable>
      ) : null}
    </View>
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
