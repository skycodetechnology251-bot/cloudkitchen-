import React, { useEffect } from 'react';
import { ActivityIndicator, Pressable, Text, View } from 'react-native';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import { AuthProvider, useAuth } from './src/auth';
import { CartProvider } from './src/cart';
import GlassTabBar from './src/nav/GlassTabBar';
import { C } from './src/shared/theme';
import LoginScreen from './src/screens/LoginScreen';
import OtpScreen from './src/screens/OtpScreen';
import ProfileSetupScreen from './src/screens/ProfileSetupScreen';
import HealthScreen from './src/screens/HealthScreen';
import HomeScreen from './src/screens/HomeScreen';
import MenuScreen from './src/screens/MenuScreen';
import DishScreen from './src/screens/DishScreen';
import CheckoutScreen from './src/screens/CheckoutScreen';
import PayScreen from './src/screens/PayScreen';
import OrdersScreen from './src/screens/OrdersScreen';
import OrderScreen from './src/screens/OrderScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import WalletScreen from './src/screens/WalletScreen';
import AddressesScreen from './src/screens/AddressesScreen';
import AddressFormScreen from './src/screens/AddressFormScreen';
import NotificationsScreen from './src/screens/NotificationsScreen';
import WaterScreen from './src/screens/WaterScreen';
import { startWellness } from './src/wellness';
import WorkoutScreen from './src/screens/WorkoutScreen';
import CoachScreen from './src/screens/CoachScreen';

/** Catches any screen error and shows a friendly retry screen instead of closing the app. */
class Guard extends React.Component {
  constructor(p) { super(p); this.state = { error: null }; }
  static getDerivedStateFromError(error) { return { error }; }
  componentDidCatch(error) { console.error('Screen error', error); }
  render() {
    if (!this.state.error) return this.props.children;
    const { Text: T, View: V, Pressable: P } = require('react-native');
    return (
      <V style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 28, backgroundColor: '#F3F7F5', gap: 12 }}>
        <T style={{ fontSize: 44 }}>🍛</T>
        <T style={{ fontSize: 20, fontWeight: '800', color: '#12302A', textAlign: 'center' }}>Something went wrong</T>
        <T style={{ fontSize: 14, color: '#56706A', textAlign: 'center' }}>Sorry about that. Tap below to try again. If it keeps happening, please contact support.</T>
        <P accessibilityRole="button" onPress={() => this.setState({ error: null })} style={{ marginTop: 8, backgroundColor: '#0F5C4D', paddingHorizontal: 26, minHeight: 48, borderRadius: 999, justifyContent: 'center' }}>
          <T style={{ color: '#fff', fontWeight: '800', fontSize: 16 }}>Try again</T>
        </P>
      </V>
    );
  }
}


const Stack = createNativeStackNavigator();
const Tabs = createBottomTabNavigator();
const theme = { ...DefaultTheme, colors: { ...DefaultTheme.colors, background: C.bg, primary: C.teal, text: C.ink } };

function MainTabs() {
  return (
    <Tabs.Navigator screenOptions={{ headerShown: false }} tabBar={(p) => <GlassTabBar {...p} />}>
      <Tabs.Screen name="Today" component={HomeScreen} />
      <Tabs.Screen name="Menu" component={MenuScreen} />
      <Tabs.Screen name="Orders" component={OrdersScreen} />
      <Tabs.Screen name="Profile" component={ProfileScreen} />
    </Tabs.Navigator>
  );
}

/** Shown to guests on screens that need an account. */
function LoginGate({ route }) {
  const { askLogin } = useAuth();
  const what = { Orders: 'see your orders', Profile: 'see your profile', Checkout: 'place your order', Wallet: 'use your wallet', Addresses: 'save addresses', AddressForm: 'save addresses', Coach: 'use the wellness coach', Notifications: 'set notifications', HealthEdit: 'save food preferences', Order: 'see your order', Pay: 'pay' }[route.name] || 'continue';
  return (
    <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', padding: 28, gap: 12, backgroundColor: C.bg }}>
      <Text style={{ fontSize: 52 }}>🔐</Text>
      <Text style={{ fontSize: 22, fontWeight: '800', color: C.ink, textAlign: 'center' }}>Log in to {what}</Text>
      <Text style={{ fontSize: 15, color: C.muted, textAlign: 'center', lineHeight: 22 }}>It takes a few seconds with your mobile number. Your basket stays as it is.</Text>
      <Pressable accessibilityRole="button" onPress={askLogin} style={{ marginTop: 8, backgroundColor: '#1F8A4C', paddingHorizontal: 28, minHeight: 52, borderRadius: 999, justifyContent: 'center' }}>
        <Text style={{ color: '#fff', fontWeight: '800', fontSize: 16 }}>Log in or sign up</Text>
      </Pressable>
    </View>
  );
}

function GuestTabs() {
  return (
    <Tabs.Navigator screenOptions={{ headerShown: false }} tabBar={(p) => <GlassTabBar {...p} />}>
      <Tabs.Screen name="Today" component={HomeScreen} />
      <Tabs.Screen name="Menu" component={MenuScreen} />
      <Tabs.Screen name="Orders" component={LoginGate} />
      <Tabs.Screen name="Profile" component={LoginGate} />
    </Tabs.Navigator>
  );
}

function Root() {
  const { ready, user, guest } = useAuth();
  if (!ready) return <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: C.teal }}><ActivityIndicator color="#fff" /></View>;
  const needsProfile = user && (user.name === 'New customer' || !user.name);
  return (
    <Stack.Navigator screenOptions={{ headerShown: false, animation: 'slide_from_right', contentStyle: { backgroundColor: C.bg } }}>
      {guest ? (
        <>
          <Stack.Screen name="Main" component={GuestTabs} />
          <Stack.Screen name="Dish" component={DishScreen} options={{ animation: 'fade_from_bottom' }} />
          <Stack.Screen name="Water" component={WaterScreen} />
          <Stack.Screen name="Workout" component={WorkoutScreen} />
          {['Checkout', 'Pay', 'Order', 'Wallet', 'Addresses', 'AddressForm', 'HealthEdit', 'Notifications', 'Coach'].map((n) => <Stack.Screen key={n} name={n} component={LoginGate} />)}
        </>
      ) : !user ? (
        <>
          <Stack.Screen name="Login" component={LoginScreen} />
          <Stack.Screen name="Otp" component={OtpScreen} />
        </>
      ) : needsProfile ? (
        <>
          <Stack.Screen name="ProfileSetup" component={ProfileSetupScreen} />
          <Stack.Screen name="Health" component={HealthScreen} />
        </>
      ) : (
        <>
          <Stack.Screen name="Main" component={MainTabs} />
          <Stack.Screen name="Dish" component={DishScreen} options={{ animation: 'fade_from_bottom' }} />
          <Stack.Screen name="Checkout" component={CheckoutScreen} />
          <Stack.Screen name="Pay" component={PayScreen} options={{ presentation: 'modal' }} />
          <Stack.Screen name="Order" component={OrderScreen} />
          <Stack.Screen name="Wallet" component={WalletScreen} />
          <Stack.Screen name="Addresses" component={AddressesScreen} />
          <Stack.Screen name="AddressForm" component={AddressFormScreen} />
          <Stack.Screen name="HealthEdit" component={HealthScreen} />
          <Stack.Screen name="Notifications" component={NotificationsScreen} />
          <Stack.Screen name="Water" component={WaterScreen} />
          <Stack.Screen name="Workout" component={WorkoutScreen} />
          <Stack.Screen name="Coach" component={CoachScreen} />
        </>
      )}
    </Stack.Navigator>
  );
}

export default function App() {
  useEffect(() => startWellness(), []);
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <CartProvider>
          <NavigationContainer theme={theme}>
            <StatusBar style="dark" />
            <Guard><Root /></Guard>
          </NavigationContainer>
        </CartProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
