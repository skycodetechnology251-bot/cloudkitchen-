import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Platform, View } from 'react-native';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { StatusBar } from 'expo-status-bar';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { api, loadToken, saveToken } from './src/shared/api';
import { C } from './src/shared/theme';
import LoginScreen from './src/screens/LoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import PickupScreen from './src/screens/PickupScreen';
import RouteScreen from './src/screens/RouteScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import TabBar from './src/nav/TabBar';
import DeliverScreen from './src/screens/DeliverScreen';
import { RiderCtx } from './src/rider';

const Stack = createNativeStackNavigator();
const Tabs = createBottomTabNavigator();

function MainTabs() {
  return (
    <Tabs.Navigator screenOptions={{ headerShown: false }} tabBar={(p) => <TabBar {...p} />}>
      <Tabs.Screen name="Today" component={HomeScreen} />
      <Tabs.Screen name="Route" component={RouteScreen} />
      <Tabs.Screen name="Profile" component={ProfileScreen} />
    </Tabs.Navigator>
  );
}

Notifications.setNotificationHandler({ handleNotification: async () => ({ shouldShowBanner: true, shouldShowList: true, shouldPlaySound: true, shouldSetBadge: false }) });

async function registerPush() {
  try {
    if (!Device.isDevice) return;
    if (Platform.OS === 'android') await Notifications.setNotificationChannelAsync('default', { name: 'Delivery offers', importance: Notifications.AndroidImportance.MAX, sound: 'default' });
    let { status } = await Notifications.getPermissionsAsync();
    if (status !== 'granted') status = (await Notifications.requestPermissionsAsync()).status;
    if (status !== 'granted') return;
    const projectId = Constants.expoConfig?.extra?.eas?.projectId || undefined;
    const { data } = await Notifications.getExpoPushTokenAsync(projectId ? { projectId } : undefined);
    await api('/me/devices', { method: 'POST', body: { token: data, platform: Platform.OS } });
  } catch (e) {}
}

export default function App() {
  const [ready, setReady] = useState(false);
  const [user, setUser] = useState(null);
  useEffect(() => { (async () => { if (await loadToken()) { try { const u = await api('/me'); if (u.role === 'rider') setUser(u); else await saveToken(null); } catch (e) { await saveToken(null); } } setReady(true); })(); }, []);
  const signIn = async (r) => { await saveToken(r.token); setUser(r.user); registerPush(); };
  const signOut = async () => { try { await api('/rider/duty', { method: 'POST', body: { on_duty: false } }); await api('/auth/logout', { method: 'POST' }); } catch (e) {} await saveToken(null); setUser(null); };

  if (!ready) return <View style={{ flex: 1, alignItems: 'center', justifyContent: 'center', backgroundColor: C.blue }}><ActivityIndicator color="#fff" /></View>;
  return (
    <SafeAreaProvider>
      <RiderCtx.Provider value={{ user, signIn, signOut }}>
        <NavigationContainer theme={{ ...DefaultTheme, colors: { ...DefaultTheme.colors, background: '#F1F5FA', primary: C.blue } }}>
          <StatusBar style="dark" />
          <Stack.Navigator screenOptions={{ headerShown: false, animation: 'slide_from_right' }}>
            {!user ? <Stack.Screen name="Login" component={LoginScreen} /> : (
              <>
                <Stack.Screen name="Main" component={MainTabs} />
                <Stack.Screen name="Pickup" component={PickupScreen} />
                <Stack.Screen name="Deliver" component={DeliverScreen} />
              </>
            )}
          </Stack.Navigator>
        </NavigationContainer>
      </RiderCtx.Provider>
    </SafeAreaProvider>
  );
}
