/**
 * Drinking-water reminders. Everything stays on the phone: settings and the daily glass count are kept in
 * secure storage, and reminders are local notifications scheduled every day between the chosen hours.
 *
 * Watches: Android shows these notifications on a paired Wear OS watch, and iPhone shows them on a paired
 * Apple Watch, with the same two buttons ("I drank a glass" and "In 30 min").
 */
import { DeviceEventEmitter, Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';

const K_SETTINGS = 'water_settings';
const K_LOG = 'water_log';
const K_SEEN = 'water_seen';
const CHANNEL = 'water';
const CATEGORY = 'water';
const PREFIX = 'water-';
const SNOOZE_ID = 'water-snooze';
export const WATER_EVENT = 'water:changed';

export const DEFAULTS = { enabled: false, every: 120, start: 8, end: 20, goal: 8, glassMl: 250 };

const TIPS = [
  'A glass now keeps you fresh for the afternoon.',
  'Small sips through the day are easier than a lot at once.',
  'Thirsty or hungry? Try a glass of water first.',
  'Chennai heat: a glass of water now, please.',
  'Keep a bottle on your desk to make this easy.',
  'Buttermilk and tender coconut count too.',
  'Water break! Stretch and sip.',
];

const pad = (n) => String(n).padStart(2, '0');
export const todayKey = (d = new Date()) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
export const fmtTime = (mins) => {
  const h = Math.floor(mins / 60) % 24, m = mins % 60, h12 = ((h + 11) % 12) + 1;
  return `${h12}:${pad(m)} ${h < 12 ? 'AM' : 'PM'}`;
};

async function read(key, fallback) {
  try { const v = await SecureStore.getItemAsync(key); return v ? JSON.parse(v) : fallback; } catch (e) { return fallback; }
}
async function write(key, value) {
  try { await SecureStore.setItemAsync(key, JSON.stringify(value)); } catch (e) { /* storage full or unavailable */ }
}

export async function getSettings() { return { ...DEFAULTS, ...(await read(K_SETTINGS, {})) }; }

/** Glass counts per day, last 14 days: { '2026-11-27': 3, ... } */
export async function getLog() { return read(K_LOG, {}); }

export async function addGlass(n = 1) {
  const log = await getLog();
  const k = todayKey();
  log[k] = Math.max(0, Math.min(40, (log[k] || 0) + n));
  const keep = Object.keys(log).sort().slice(-14);
  const trimmed = Object.fromEntries(keep.map((d) => [d, log[d]]));
  await write(K_LOG, trimmed);
  return trimmed;
}

/** Reminder times (minutes after midnight) between start and end hours, every `every` minutes. */
export function slots(s) {
  const out = [];
  for (let t = s.start * 60; t <= s.end * 60 && out.length < 24; t += s.every) out.push(t);
  return out;
}

/** Android channel and the two action buttons. Safe to call on every app start. */
export async function setupWater() {
  try {
    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync(CHANNEL, {
        name: 'Drinking water reminders',
        description: 'Gentle reminders to drink water. Also shown on your watch.',
        importance: Notifications.AndroidImportance.HIGH,
        vibrationPattern: [0, 200, 120, 200],
        lightColor: '#2F80ED',
      });
    }
    await Notifications.setNotificationCategoryAsync(CATEGORY, [
      { identifier: 'drank', buttonTitle: '💧 I drank a glass', options: { opensAppToForeground: false } },
      { identifier: 'snooze', buttonTitle: '⏰ In 30 min', options: { opensAppToForeground: false } },
    ]);
  } catch (e) { /* notifications unavailable */ }
}

async function cancelWater() {
  const all = await Notifications.getAllScheduledNotificationsAsync();
  await Promise.all(all.filter((n) => n.identifier.startsWith(PREFIX)).map((n) => Notifications.cancelScheduledNotificationAsync(n.identifier)));
}

const content = (i, goal) => ({
  title: '💧 Time for a glass of water',
  body: `${TIPS[i % TIPS.length]} Goal: ${goal} glasses today.`,
  categoryIdentifier: CATEGORY,
  data: { kind: 'water' },
  sound: 'default',
});

/** Save settings and (re)schedule the daily reminders. Returns false if notifications are not allowed. */
export async function saveSettings(next) {
  const s = { ...DEFAULTS, ...next };
  await write(K_SETTINGS, s);
  await cancelWater();
  if (!s.enabled) return true;
  let { status } = await Notifications.getPermissionsAsync();
  if (status !== 'granted') status = (await Notifications.requestPermissionsAsync()).status;
  if (status !== 'granted') return false;
  await setupWater();
  const times = slots(s);
  for (let i = 0; i < times.length; i++) {
    await Notifications.scheduleNotificationAsync({
      identifier: `${PREFIX}${i}`,
      content: content(i, s.goal),
      trigger: { type: Notifications.SchedulableTriggerInputTypes.DAILY, hour: Math.floor(times[i] / 60), minute: times[i] % 60, channelId: CHANNEL },
    });
  }
  return true;
}

export async function snooze(minutes = 30) {
  const s = await getSettings();
  await Notifications.scheduleNotificationAsync({
    identifier: SNOOZE_ID,
    content: content(new Date().getHours(), s.goal),
    trigger: { type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL, seconds: minutes * 60, channelId: CHANNEL },
  });
}

/**
 * Handle a tap on a water notification button (phone or watch). Each tap is counted once, even if the
 * app sees the same response again when it next opens.
 */
export async function handleWaterResponse(resp) {
  const req = resp?.notification?.request;
  if (!req || req.content?.data?.kind !== 'water') return false;
  const action = resp.actionIdentifier;
  if (action !== 'drank' && action !== 'snooze') return false;
  const tag = `${req.identifier}@${resp.notification.date}:${action}`;
  const seen = await read(K_SEEN, []);
  if (seen.includes(tag)) return false;
  await write(K_SEEN, [...seen.slice(-30), tag]);
  if (action === 'drank') { await addGlass(1); DeviceEventEmitter.emit(WATER_EVENT); } else await snooze(30);
  try { await Notifications.dismissNotificationAsync(req.identifier); } catch (e) { /* already gone */ }
  return true;
}

/** Count a button tapped while the app was closed (the system keeps the last one for us). */
export async function processLastResponse() {
  try {
    const get = Notifications.getLastNotificationResponseAsync;
    const last = get ? await get() : null;
    if (last) await handleWaterResponse(last);
  } catch (e) { /* nothing to do */ }
}

/** One app-wide listener for taps on water buttons (phone or watch). Returns an unsubscribe function. */
export function listenWater() {
  const sub = Notifications.addNotificationResponseReceivedListener((r) => { handleWaterResponse(r); });
  return () => sub.remove();
}
