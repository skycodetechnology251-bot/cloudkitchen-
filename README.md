# Daily Table — pilot MVP

Home-style lunch delivery for Chennai. Lunch is cooked in neighbourhood kitchens, dishes come with clear nutrition, and health preferences are optional.

| Folder | What it is | Built with |
|---|---|---|
| `backend/` | Showcase website, admin dashboard, kitchen board, POS, GST invoices, reports, and the API for both apps, with a browser setup page | Laravel 12 + MySQL 8 |
| `mobile/customer/` | **Daily Table** customer app for Android and iOS | React Native (Expo SDK 54) |
| `mobile/rider/` | **Daily Table Rider** delivery-partner app for Android and iOS | React Native (Expo SDK 54) |
| `.github/workflows/android-apk.yml` | Builds both APKs on GitHub | GitHub Actions |

## 1. Install the website and backend

Everything is included in the `backend` folder: Laravel and every package, so you don't need Composer or a terminal.

1. Create a MySQL database and user in your hosting panel.
2. Upload and extract `backend`. Point **wownaturals.in** at `backend/public`, and turn on SSL.
3. Open **https://wownaturals.in**. The setup page at `/install` checks the server and asks for the database details and your admin login, then installs everything and switches itself off.

Later updates: upload the new files, then open **Admin → System → Back up and update now**. The full guide, including the cron job and Razorpay webhook, is in [backend/README.md](backend/README.md).

## 2. Get the Android APK files

The APKs are built on GitHub, because building them needs the Android SDK:

1. Create a **private** GitHub repository and push this whole folder to it:
   ```bash
   git init && git add . && git commit -m "Daily Table pilot"
   git branch -M main
   git remote add origin https://github.com/<you>/daily-table.git
   git push -u origin main
   ```
2. Both apps are already set to talk to **https://wownaturals.in**. To point them at a different server, go to **Settings → Secrets and variables → Actions → Variables** and add `API_URL`.
3. Open **Actions → Android APK → Run workflow**. The build takes about 15 minutes.
4. Open the finished run. Under **Artifacts**, download:
   - `daily-table-customer-apk`
   - `daily-table-rider-apk`

   Each one is a zip with the `.apk` inside.
5. Copy the APK to an Android phone and open it. You will need to allow "Install unknown apps" for your file manager.

If you push a tag such as `git tag v1.0.0 && git push --tags`, the workflow also attaches both APKs to a GitHub Release.

**Optional settings in the same place:**

- **Release signing:** add the secrets `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS` and `ANDROID_KEY_PASSWORD`. Without them the APK is signed with a debug key. That is fine for testing, but not for the Play Store.
- **Google sign-in:** add the variables `GOOGLE_ANDROID_CLIENT_ID` and `GOOGLE_WEB_CLIENT_ID`. Until they are set, the Google button stays hidden.

## 3. iPhone (iOS)

The same code runs on iOS. Build it with Expo EAS. You need an Apple Developer account.

```bash
cd mobile/customer
npm install && npx expo install --fix
npx eas-cli build --platform ios     # then: npx eas-cli submit --platform ios
```

## 4. Try it on your computer during development

```bash
cd backend && php artisan serve --host 0.0.0.0     # open http://localhost:8000/install first
cd mobile/customer && npm install && npx expo start  # scan the QR code with Expo Go
```

Before you run the app, set `expo.extra.apiUrl` in `mobile/*/app.json` to your computer's LAN address, for example `http://192.168.1.20:8000`.
