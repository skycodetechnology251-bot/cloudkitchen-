/**
 * Shared Expo config for both apps. The values come from app-config.json, which the Laravel
 * website writes from its own settings (php artisan dailytable:mobile-config, or automatically
 * when you save Admin → Integrations). So the apps always use the website's configuration.
 *
 * Optional overrides when building: API_URL, APP_VERSION_CODE (GitHub sets GITHUB_RUN_NUMBER).
 */
const fs = require('fs');
const path = require('path');

const DEFAULTS = {
  api_url: 'https://wownaturals.in',
  brand: 'Daily Table',
  version: '1.0.0',
  customer: { name: 'Daily Table', package: 'in.dailytable.app', scheme: 'dailytable', color: '#0F5C4D' },
  rider: { name: 'Daily Table Rider', package: 'in.dailytable.rider', scheme: 'dailytablerider', color: '#1F5FA8' },
  google: { android_client_id: null, ios_client_id: null, web_client_id: null },
  eas_project_id: null,
  support: { phone: null, whatsapp: null, email: null },
  links: {},
};

function shared() {
  const file = path.join(__dirname, 'app-config.json');
  let cfg = {};
  try { cfg = JSON.parse(fs.readFileSync(file, 'utf8')); } catch (e) { /* use defaults */ }
  const merged = { ...DEFAULTS, ...cfg, customer: { ...DEFAULTS.customer, ...cfg.customer }, rider: { ...DEFAULTS.rider, ...cfg.rider }, google: { ...DEFAULTS.google, ...cfg.google }, support: { ...DEFAULTS.support, ...cfg.support } };
  if (process.env.API_URL) merged.api_url = process.env.API_URL;
  merged.api_url = String(merged.api_url).replace(/\/$/, '');
  return merged;
}

/** Build the Expo config for "customer" or "rider". */
module.exports = function makeConfig(which) {
  const c = shared();
  const app = c[which];
  const versionCode = Number(process.env.APP_VERSION_CODE || process.env.GITHUB_RUN_NUMBER || 1);
  const location = which === 'rider'
    ? 'While you are on duty, ' + c.brand + ' shares your location with the kitchen so it can assign nearby deliveries.'
    : c.brand + ' uses your location to fill in your delivery address and pick your saved address when you are there.';

  return {
    expo: {
      name: app.name,
      slug: which === 'rider' ? 'daily-table-rider' : 'daily-table',
      scheme: app.scheme,
      version: c.version,
      orientation: 'portrait',
      userInterfaceStyle: 'light',
      icon: './assets/icon.png',
      splash: { image: './assets/icon.png', resizeMode: 'contain', backgroundColor: app.color },
      ios: { bundleIdentifier: app.package, supportsTablet: false, infoPlist: { NSLocationWhenInUseUsageDescription: location } },
      android: {
        package: app.package,
        versionCode,
        adaptiveIcon: { foregroundImage: './assets/icon.png', backgroundColor: app.color },
        permissions: ['ACCESS_COARSE_LOCATION', 'ACCESS_FINE_LOCATION', 'POST_NOTIFICATIONS'],
      },
      plugins: ['expo-secure-store', ['expo-location', { locationWhenInUsePermission: location }], ['expo-notifications', { color: app.color }]],
      extra: {
        apiUrl: c.api_url,
        brand: c.brand,
        googleAndroidClientId: c.google.android_client_id || '',
        googleIosClientId: c.google.ios_client_id || '',
        googleWebClientId: c.google.web_client_id || '',
        support: c.support,
        links: c.links,
        eas: { projectId: c.eas_project_id || undefined },
      },
    },
  };
};
