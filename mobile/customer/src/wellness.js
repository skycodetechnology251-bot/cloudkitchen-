/**
 * Wellness: starts water and workout reminders, handles their watch/phone buttons, and (only when the
 * customer has switched the coach on) sends the daily totals to the server so the coach can learn the
 * pattern and give advice.
 */
import * as Notifications from 'expo-notifications';
import { api } from './shared/api';
import { getLog as waterLog, getSettings as waterSettings, handleWaterResponse, scheduleCatchUp, setupWater, todayKey } from './water';
import { getLog as workoutLog, getSettings as workoutSettings, handleWorkoutResponse, setupWorkout } from './workout';

async function handle(resp) {
  if (!resp) return;
  await handleWaterResponse(resp);
  await handleWorkoutResponse(resp);
}

/** Call once when the app starts. Returns an unsubscribe function. */
export function startWellness() {
  setupWater(); setupWorkout();
  scheduleCatchUp().catch(() => {});
  (async () => {
    try { const get = Notifications.getLastNotificationResponseAsync; if (get) await handle(await get()); } catch (e) { /* nothing */ }
  })();
  const sub = Notifications.addNotificationResponseReceivedListener((r) => { handle(r); });
  return () => sub.remove();
}

/** Count a button tapped while the app was closed. */
export async function processLast() {
  try { const get = Notifications.getLastNotificationResponseAsync; if (get) await handle(await get()); } catch (e) { /* nothing */ }
}

/** Last 14 days of water and workouts, in the shape the server expects. */
export async function wellnessDays() {
  const [wl, ws, kl, ks] = await Promise.all([waterLog(), waterSettings(), workoutLog(), workoutSettings()]);
  const out = [];
  for (let i = 13; i >= 0; i--) {
    const d = new Date(); d.setDate(d.getDate() - i);
    const k = todayKey(d);
    if (wl[k] == null && !kl[k]?.length) continue;
    out.push({ date: k, glasses: wl[k] || 0, glass_goal: ws.goal, workout_goal: ks.goal, workouts: (kl[k] || []).map(({ type, minutes, intensity }) => ({ type, minutes, intensity })) });
  }
  return out;
}

let lastSync = 0;
/** Send the days to the coach (only if switched on). Quietly does nothing otherwise. */
export async function syncWellness(user, force = false) {
  if (!user?.coach_on) return false;
  if (!force && Date.now() - lastSync < 60000) return true;
  const days = await wellnessDays();
  if (!days.length) return true;
  try { await api('/me/wellness', { method: 'PUT', body: { days } }); lastSync = Date.now(); return true; } catch (e) { return false; }
}

export const coachOn = (on) => api('/me/coach/consent', { method: 'PUT', body: { on } });
export const getCoach = (kitchenId) => api('/me/coach', { query: kitchenId ? { kitchen_id: kitchenId } : {} });
export const getWorkoutFoods = (kitchenId) => api('/me/coach/foods', { query: { kitchen_id: kitchenId } });
