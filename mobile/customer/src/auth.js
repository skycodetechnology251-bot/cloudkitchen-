import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as Device from 'expo-device';
import Constants from 'expo-constants';
import { api, loadToken, saveToken } from './shared/api';

const Ctx = createContext(null);
export const useAuth = () => useContext(Ctx);

Notifications.setNotificationHandler({
  handleNotification: async () => ({ shouldShowBanner: true, shouldShowList: true, shouldPlaySound: true, shouldSetBadge: false }),
});

async function registerPush() {
  try {
    if (!Device.isDevice) return;
    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync('default', { name: 'Orders and reminders', importance: Notifications.AndroidImportance.HIGH });
    }
    let { status } = await Notifications.getPermissionsAsync();
    if (status !== 'granted') status = (await Notifications.requestPermissionsAsync()).status;
    if (status !== 'granted') return;
    const projectId = Constants.expoConfig?.extra?.eas?.projectId || undefined;
    const { data } = await Notifications.getExpoPushTokenAsync(projectId ? { projectId } : undefined);
    await api('/me/devices', { method: 'POST', body: { token: data, platform: Platform.OS } });
  } catch (e) {
    // Push is optional: the app keeps working with in-app cards.
  }
}

export function AuthProvider({ children }) {
  const [ready, setReady] = useState(false);
  const [user, setUser] = useState(null);
  const [guest, setGuest] = useState(false); // "Skip" on the login screen: browse without an account

  const refresh = useCallback(async () => {
    try { setUser(await api('/me')); } catch (e) { if (e.status === 401) { await saveToken(null); setUser(null); } }
  }, []);

  useEffect(() => { (async () => { if (await loadToken()) await refresh(); setReady(true); })(); }, [refresh]);

  const signIn = useCallback(async ({ token, user: u }) => { await saveToken(token); setGuest(false); setUser(u); registerPush(); }, []);
  const skip = useCallback(() => setGuest(true), []);
  /** From guest mode back to the login screen (basket is kept). */
  const askLogin = useCallback(() => setGuest(false), []);
  const signOut = useCallback(async () => { try { await api('/auth/logout', { method: 'POST' }); } catch (e) {} await saveToken(null); setUser(null); }, []);

  return <Ctx.Provider value={{ ready, user, guest: !user && guest, setUser, refresh, signIn, signOut, skip, askLogin }}>{children}</Ctx.Provider>;
}
