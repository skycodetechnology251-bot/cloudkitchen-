import Constants from 'expo-constants';
import * as SecureStore from 'expo-secure-store';

// The server address comes from the Laravel website's settings (mobile/app-config.json → app.config.js).
/** Support contacts from the website settings (Admin → Integrations → Mobile apps). */
export const SUPPORT = Constants.expoConfig?.extra?.support || {};
/** Opens WhatsApp to the support number (or a share sheet if none is set). */
export const whatsappUrl = (text) => `https://wa.me/${(SUPPORT.whatsapp || '').replace(/\D/g, '')}?text=${encodeURIComponent(text)}`;

export const BASE = (Constants.expoConfig?.extra?.apiUrl || 'http://10.0.2.2:8000').replace(/\/$/, '');
const API = BASE + '/api/v1';
const KEY = 'dt_token';

let token = null;
export async function loadToken() { token = await SecureStore.getItemAsync(KEY); return token; }
export async function saveToken(t) { token = t; if (t) await SecureStore.setItemAsync(KEY, t); else await SecureStore.deleteItemAsync(KEY); }

export class ApiError extends Error {
  constructor(message, status, errors) { super(message); this.status = status; this.errors = errors || {}; }
}

export async function api(path, { method = 'GET', body, query } = {}) {
  const qs = query ? '?' + new URLSearchParams(Object.entries(query).filter(([, v]) => v !== undefined && v !== null)).toString() : '';
  let res;
  try {
    res = await fetch(API + path + qs, {
      method,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(token ? { Authorization: 'Bearer ' + token } : {}) },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (e) {
    throw new ApiError('No internet connection. Check your network and try again.', 0);
  }
  const text = await res.text();
  let data = null;
  try { data = text ? JSON.parse(text) : null; } catch (e) { data = { message: text }; }
  if (!res.ok) {
    const first = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
    throw new ApiError(first || data?.message || 'Something went wrong.', res.status, data?.errors);
  }
  return data;
}

export const rupees = (paise, dp = 0) => '₹' + (Number(paise || 0) / 100).toLocaleString('en-IN', { minimumFractionDigits: dp, maximumFractionDigits: dp });

/** Today's date (YYYY-MM-DD) on the phone's clock, not UTC. */
export const localToday = () => { const d = new Date(); return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10); };

/** "13:00" → "1:00 PM"; short=true → "1". */
export const clock = (t, short = false) => { const [h, m] = String(t).split(':').map(Number); const h12 = ((h + 11) % 12) + 1; return short ? (m ? `${h12}:${String(m).padStart(2, '0')}` : String(h12)) : `${h12}:${String(m || 0).padStart(2, '0')} ${h < 12 ? 'AM' : 'PM'}`; };
/** "12:00-13:00" → "12:00 – 1:00 PM". */
export const slotLabel = (s) => { const [a, b] = String(s).split('-'); return `${clock(a).replace(/ (AM|PM)$/, '')} – ${clock(b)}`; };
/** Whole delivery window from a list of slots, short: ["12:00-13:00","13:00-14:00"] → "12–2". */
export const windowShort = (slots) => (slots?.length ? `${clock(slots[0].split('-')[0], true)}–${clock(slots[slots.length - 1].split('-')[1], true)}` : '12–1');
