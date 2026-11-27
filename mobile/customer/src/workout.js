/**
 * Daily workout: log activities, keep a streak, and get one motivating reminder a day (a different
 * message each weekday). The reminder has two buttons that also work on Wear OS and Apple Watch:
 * "Did 20 min" logs it, "In 1 hour" reminds again later.
 */
import { DeviceEventEmitter, Platform } from 'react-native';
import * as Notifications from 'expo-notifications';
import * as SecureStore from 'expo-secure-store';
import { todayKey } from './water';

const K_SETTINGS = 'workout_settings';
const K_LOG = 'workout_log';
const K_SEEN = 'workout_seen';
const CHANNEL = 'workout';
const CATEGORY = 'workout';
const PREFIX = 'workout-';
export const WORKOUT_EVENT = 'workout:changed';

export const DEFAULTS = { enabled: false, hour: 7, minute: 0, goal: 30, fav: 'walk' };

export const TYPES = [
  ['walk', 'Walk', '🚶'], ['run', 'Run', '🏃'], ['yoga', 'Yoga', '🧘'], ['gym', 'Gym', '🏋️'], ['cycling', 'Cycling', '🚴'],
  ['home', 'Home workout', '🤸'], ['dance', 'Dance', '💃'], ['sports', 'Sports', '🏸'], ['swim', 'Swimming', '🏊'],
];
export const typeLabel = (t) => (TYPES.find((x) => x[0] === t) || TYPES[5])[1];
export const typeIcon = (t) => (TYPES.find((x) => x[0] === t) || TYPES[5])[2];

/** One simple routine for each day of the week (0 = Sunday). General, low-risk moves. */
export const ROUTINES = [
  { title: 'Easy walk & stretch', minutes: 20, type: 'walk', steps: ['15-minute easy walk', 'Neck and shoulder rolls, 1 minute', 'Calf and hamstring stretch, 30 seconds each side', '5 slow deep breaths'] },
  { title: 'Brisk walk', minutes: 30, type: 'walk', steps: ['5 minutes easy pace to warm up', '20 minutes brisk — you can talk but not sing', '5 minutes slow walk to cool down'] },
  { title: 'Home strength', minutes: 15, type: 'home', steps: ['3 rounds of:', '10 squats (sit-to-stand from a chair is fine)', '8 wall push-ups', '10 step-back lunges each leg, hold a wall if needed', '20-second plank (knees down is fine)'] },
  { title: 'Yoga & breathing', minutes: 20, type: 'yoga', steps: ['Cat–cow, 1 minute', 'Child’s pose, 1 minute', 'Cobra, 30 seconds × 3', 'Seated forward bend, 1 minute', 'Anulom vilom breathing, 5 minutes'] },
  { title: 'Stairs or skipping', minutes: 15, type: 'home', steps: ['Warm up 3 minutes', '6 rounds: 1 minute active (stairs or skipping), 1 minute easy', 'Cool down and stretch 2 minutes'] },
  { title: 'Dance or play', minutes: 30, type: 'dance', steps: ['Put on your favourite songs and dance', 'Or play badminton, cricket or football with friends', 'Anything that makes you breathe a little faster counts'] },
  { title: 'Long walk or cycle', minutes: 40, type: 'cycling', steps: ['40 minutes at an easy, steady pace', 'Go somewhere green if you can', 'Carry water and sip every 10–15 minutes'] },
];

const NUDGES = [
  'A short walk today keeps the streak going. Your body will thank you! 🚶',
  'Monday energy! 30 minutes of brisk walking today? 💪',
  '15 minutes of home strength — no gym needed. Let’s go!',
  'Stretch, breathe, relax: 20 minutes of yoga today 🧘',
  'Quick and fun: stairs or skipping for 15 minutes today 🔥',
  'Friday! Dance, play or move for 30 minutes 💃',
  'Weekend walk or ride — enjoy 40 easy minutes outside 🚴',
];

async function read(key, fallback) {
  try { const v = await SecureStore.getItemAsync(key); return v ? JSON.parse(v) : fallback; } catch (e) { return fallback; }
}
async function write(key, value) {
  try { await SecureStore.setItemAsync(key, JSON.stringify(value)); } catch (e) { /* storage unavailable */ }
}

export async function getSettings() { return { ...DEFAULTS, ...(await read(K_SETTINGS, {})) }; }
/** { '2026-11-28': [{ type, minutes, intensity, at }], ... } last 14 days */
export async function getLog() { return read(K_LOG, {}); }
export const minutesOn = (log, key) => (log[key] || []).reduce((n, w) => n + (w.minutes || 0), 0);

export function streak(log) {
  const d = new Date();
  if (minutesOn(log, todayKey(d)) < 10) d.setDate(d.getDate() - 1);
  let n = 0;
  while (minutesOn(log, todayKey(d)) >= 10) { n++; d.setDate(d.getDate() - 1); }
  return n;
}

export async function addWorkout(entry) {
  const log = await getLog();
  const k = todayKey();
  log[k] = [...(log[k] || []), { type: entry.type || 'walk', minutes: Math.max(1, Math.min(300, entry.minutes || 20)), intensity: entry.intensity || 'moderate', at: Date.now() }].slice(-20);
  const keep = Object.keys(log).sort().slice(-14);
  const trimmed = Object.fromEntries(keep.map((d) => [d, log[d]]));
  await write(K_LOG, trimmed);
  DeviceEventEmitter.emit(WORKOUT_EVENT);
  return trimmed;
}

export async function removeLast() {
  const log = await getLog();
  const k = todayKey();
  log[k] = (log[k] || []).slice(0, -1);
  await write(K_LOG, log);
  DeviceEventEmitter.emit(WORKOUT_EVENT);
  return log;
}

export async function setupWorkout() {
  try {
    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync(CHANNEL, {
        name: 'Daily workout reminder', description: 'One motivating reminder a day. Also shown on your watch.',
        importance: Notifications.AndroidImportance.HIGH, vibrationPattern: [0, 250, 150, 250], lightColor: '#F2711C',
      });
    }
    await Notifications.setNotificationCategoryAsync(CATEGORY, [
      { identifier: 'log20', buttonTitle: '✅ Did 20 min', options: { opensAppToForeground: false } },
      { identifier: 'later', buttonTitle: '⏰ In 1 hour', options: { opensAppToForeground: false } },
    ]);
  } catch (e) { /* notifications unavailable */ }
}

async function cancelAll() {
  const all = await Notifications.getAllScheduledNotificationsAsync();
  await Promise.all(all.filter((n) => n.identifier.startsWith(PREFIX)).map((n) => Notifications.cancelScheduledNotificationAsync(n.identifier)));
}

const content = (weekday) => ({
  title: `Today: ${ROUTINES[weekday].title} · ${ROUTINES[weekday].minutes} min`,
  body: NUDGES[weekday],
  categoryIdentifier: CATEGORY,
  data: { kind: 'workout' },
  sound: 'default',
});

/** Save settings and schedule one reminder per weekday (each with that day's routine). */
export async function saveSettings(next) {
  const s = { ...DEFAULTS, ...next };
  await write(K_SETTINGS, s);
  await cancelAll();
  if (!s.enabled) return true;
  let { status } = await Notifications.getPermissionsAsync();
  if (status !== 'granted') status = (await Notifications.requestPermissionsAsync()).status;
  if (status !== 'granted') return false;
  await setupWorkout();
  for (let wd = 0; wd < 7; wd++) {
    await Notifications.scheduleNotificationAsync({
      identifier: `${PREFIX}${wd}`,
      content: content(wd),
      // Expo weekdays: 1 = Sunday … 7 = Saturday.
      trigger: { type: Notifications.SchedulableTriggerInputTypes.WEEKLY, weekday: wd + 1, hour: s.hour, minute: s.minute, channelId: CHANNEL },
    });
  }
  return true;
}

export async function handleWorkoutResponse(resp) {
  const req = resp?.notification?.request;
  if (!req || req.content?.data?.kind !== 'workout') return false;
  const action = resp.actionIdentifier;
  if (action !== 'log20' && action !== 'later') return false;
  const tag = `${req.identifier}@${resp.notification.date}:${action}`;
  const seen = await read(K_SEEN, []);
  if (seen.includes(tag)) return false;
  await write(K_SEEN, [...seen.slice(-30), tag]);
  if (action === 'log20') {
    const s = await getSettings();
    await addWorkout({ type: s.fav, minutes: 20, intensity: 'moderate' });
  } else {
    await Notifications.scheduleNotificationAsync({
      identifier: `${PREFIX}later`, content: content(new Date().getDay()),
      trigger: { type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL, seconds: 3600, channelId: CHANNEL },
    });
  }
  try { await Notifications.dismissNotificationAsync(req.identifier); } catch (e) { /* already gone */ }
  return true;
}
