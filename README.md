# Daily Table — one Laravel project: website, admin, app backend and the mobile apps

One Laravel 12 project with **one configuration**. It contains:

- the Laravel website, admin and API (this folder)
- the two React Native apps in **`mobile/`**:
  - `mobile/customer`: the Daily Table app for customers
  - `mobile/rider`: the Daily Table Rider app for delivery partners

**How the apps share the configuration:** the apps take their settings from Laravel:

- the website address (`APP_URL`)
- the site name
- in Admin → Integrations: **Google sign-in** and **Mobile apps** (app names, Android package names, version, support phone and WhatsApp)

Laravel writes these to `mobile/app-config.json`, and also serves them at `https://wownaturals.in/api/v1/app-config`. This happens:

- when setup finishes
- after every update
- whenever you save those settings

The APK build on GitHub reads the same settings from your live site, so you configure only Laravel. To write the file by hand, run `php artisan dailytable:mobile-config`.

The Laravel project serves:

- the **website** at https://wownaturals.in
- the **admin dashboard**, **kitchen board**, **counter POS** and **GST invoices**
- the **API** (`/api/v1`) that the customer app and the rider app use

**Everything is included.** The `vendor` folder already holds Laravel and every package, so you don't need Composer, Node or a terminal. You upload the files and open the setup page in a browser.

## Requirements

- PHP 8.2 or newer, with these extensions: ctype, curl, dom, fileinfo, filter, mbstring, openssl, pdo_mysql, tokenizer, xml (intl is optional)
- MySQL 8.0+ or MariaDB 10.4+
- SSL (https) turned on for the domain

## Install (shared hosting: Hostinger, cPanel and similar)

1. **Create the database.** In your hosting panel, open **MySQL Databases**. Create a database, then a user with a password, and give the user **ALL PRIVILEGES** on the database. Write down all three.
2. **Upload.** In the File Manager, open `public_html`. Delete any old files there, for example `default.php`, or an old `backend` folder. Upload `wownaturals-website.zip` and **Extract** it right there. You should now see `app`, `public`, `vendor`, `artisan` and `.htaccess` directly inside `public_html`.
3. **Domain.** Nothing to change: the included `.htaccess` sends visitors into `public/` and keeps settings files private. Optional on a VPS: point the domain's document root at `public_html/public` instead.
4. **Turn on SSL** for wownaturals.in in the panel.
5. **Open https://wownaturals.in.** You are taken to the setup page at `/install`. It:
   - checks PHP, the extensions and folder permissions, and tells you what to fix if anything is missing
   - asks for the database name, user and password from step 1
   - asks for your admin name, email and password
   - can optionally add demo data (the Adyar kitchen, 14 dishes, menus, banners, offers and demo staff)

   Click **Install Daily Table**. It creates the settings file, all tables, the roles and permissions, and your admin account. Then the setup page switches itself off: `/install` returns "not found" from then on.
6. **Add the cron job.** In the panel, open **Cron Jobs** and add a job for every minute (`* * * * *`). The command is shown on the last setup page and in **Admin → System**, for example:
   ```
   /usr/local/bin/php /home/USER/public_html/artisan schedule:run >> /dev/null 2>&1
   ```
   Without it, background tasks run after page visits instead. Unpaid reservations are still released, but the 9:30 wallet reminder and the 10:00 lunch reminder may be missed.
7. **Admin → Integrations.** Enter the keys for Razorpay, SMS (OTP), WhatsApp, push, email, Google sign-in and OpenAI. They are stored encrypted.
8. **Razorpay dashboard.** Set the webhook to `https://wownaturals.in/api/v1/webhooks/razorpay`. Turn on the events `payment.captured`, `payment.failed` and `refund.processed`.
9. **Admin → Branches, Zones, Dishes, Menus.** Add each branch (address, map pin, delivery radius, team) and create its delivery zones in the same form, then add dishes and publish each branch's menu.

### With a terminal instead (SSH or a VPS)

```bash
php artisan dailytable:install
```

This asks the same questions as the setup page. It can also create the MySQL database and user for you if you have a MySQL admin login such as `root`.

## Updating to a new version

1. Take a backup in your hosting panel. Recommended.
2. Upload the new files over the old ones. Keep your `.env` file and the `storage` folder.
3. Open **Admin → System → Back up and update now**. Or on a terminal, run `php artisan dailytable:update`.

The update does these steps in order:

- backs up the database to `storage/app/private/backups`; you can download the backups from Admin → System
- adds any new tables and columns
- adds new roles and permissions
- refreshes caches

It never deletes data. Until the update has run, visitors see a short "finishing an update" message instead of an error.

**Opening /install on a site that's already installed:** if the lock file is missing (say, after moving servers), the setup page shows again. Entering the existing database in it **updates** that database: it takes a backup first and keeps every customer, order and invoice.

## Logins

| Who | Where | How |
|---|---|---|
| Admin (you) | https://wownaturals.in/staff/login | the email and password you chose during setup |
| Staff | the same page | accounts you create in Admin → Staff |
| Customers | the Daily Table app | mobile number, then the SMS OTP, or Google |
| Riders | the Daily Table Rider app | the mobile number saved on their staff account, then the SMS OTP |

If you added demo data, the demo staff share one password. It is shown once, at the end of setup. The demo accounts are:

- `kitchen@dailytable.test`
- `cashier@dailytable.test`, with POS PIN `1234`
- `rider@dailytable.test`, rider phone `9000000001`

Remove the demo staff before going live.

**OTP codes:** until an SMS provider is set in Admin → Integrations, codes are written to `storage/logs` instead of being sent.

## What's inside

**Ordering and money**

- Orders run through these steps: confirmed, accepted, preparing, packed, picked up, delivered.
- Portions are reserved for 10 minutes while the customer pays.
- Ordering closes at 10:45. After that, the app shows tomorrow's menu.
- The wallet takes top-ups through Razorpay and pays orders first. UPI or card covers any shortfall.
- Reward points: 1 point for every ₹10 spent. 100 points are worth ₹25, and points can pay up to 20% of an order.
- GST is 5%, split into CGST 2.5% and SGST 2.5%, under SAC 996331.
- Invoice numbers run per kitchen and per financial year, for example `DT/ADY/26-27/00001`.
- Reports cover income, expenses and GST, with a CSV export.

**Delivery**

- Kitchen staff use the kitchen board to send packed orders to riders in batches.
- At pickup, the rider app shows a 3-digit counter code; the kitchen board shows the same code so counter staff can match it before handing over. The rider ticks off every package.
- The customer tells the rider a 4-digit code at the door.

**Staff access and POS**

- There are ten roles. Admin → Roles lets you edit the permissions of each role.
- Every staff role can use the counter POS. A **POS** button sits in the top bar of every staff page.
- Staff bill for the branches they work in, set in Admin → Branches → Team at this branch (or Staff → Branches they work in). Admin and operations managers can bill for every branch and switch branches from the POS button's menu.

**Salaries and payroll** (Admin → Salaries & payroll; admin and finance only)

- **Salary by role:** set how each role is paid: monthly, per day, per hour or per delivery, plus an optional extra per delivery for riders.
- **Own amount:** in Admin → Staff, anyone can have their own salary and joining date. Leave it blank to use the role's salary.
- **Payroll:** pick a month and pay each person. Add a bonus or deduction, enter days or hours for daily and hourly staff; riders' delivery pay counts their completed deliveries. Each payment is recorded as a salary expense, so it appears in Reports, and has a printable payslip. "Undo" removes a payment entered by mistake.
- Health details are visible only to dietitians and doctors.

**Not in this version:** the dietitian dashboard and AI-drafted diet plans from the designs. They are planned for the next phase. Their roles and the health-consent records are already in place.

## The mobile apps (`mobile/`)

- **Settings:** each app's `app.config.js` reads `mobile/app-config.json`, so there is no separate app configuration to keep in step with the website.
- **Android APKs:** push this project to GitHub. The **Android APK** workflow builds both apps and puts them under the run's **Artifacts** and on the `apk` branch. Before building, it fetches the latest settings from `/api/v1/app-config` on your live site.
  - To build against a different server, set the GitHub variable `API_URL`.
  - The signing-key secrets are optional: `ANDROID_KEYSTORE_BASE64`, `ANDROID_KEYSTORE_PASSWORD`, `ANDROID_KEY_ALIAS` and `ANDROID_KEY_PASSWORD`. Without them the APKs are signed with a test key.
- **What needs a new APK:** changing an app's name, package or version, or the Google IDs. Support phone and WhatsApp are read by the apps when they open.
- **iPhone:**
  ```bash
  cd mobile/customer
  npm install
  npx expo install --fix
  npx eas-cli build --platform ios
  ```
  This needs an Apple Developer account.
- **Try the apps on your computer:**
  ```bash
  php artisan serve --host 0.0.0.0
  cd mobile/customer
  npm install
  npx expo start
  ```
  First set `api_url` in `mobile/app-config.json` to your computer's address, for example `http://192.168.1.20:8000`.
- **On the server:** the `mobile/` folder is uploaded with the website but is never served to visitors. The `.htaccess` sends every request into `public/`.

## Branches

Admin → Branches lists every branch with today's orders and sales. **+ New branch** asks for:

1. Branch details: name, code (starts its invoice numbers), phone, GSTIN, FSSAI, open for orders.
2. Location: country → state → city, address, pincode, and a map pin (search, click the map, or use your current location), plus how far it delivers in km.
3. Delivery zones (at least one): tick existing zones, or create new ones as a circle around the pin. New zones start as Testing (taking orders) or Draft.
4. Team: the staff and riders who work there. They get that branch's POS and kitchen board.

A customer is served by the nearest open branch whose zone covers their address and that is within its delivery distance. Zones can be shared by several branches, and overlapping zones are fine.

## Who sees which branch

- **Head office (Admin)** sees and manages every branch: all orders, reports, income and expenses, payroll, staff, zones, and the company-wide customer wallet and points totals. Filters let you look at one branch or all together.
- **Everyone else** sees only the branches they're assigned to (Branches → Team at this branch, or Staff → Branches they work in). That covers the dashboard, orders, invoices, reports and GST export, purchases and expenses, daily menu, POS, kitchen board, payroll, staff and zones. Opening another branch's page by its link is refused.
- A branch manager can't add branches, change the salary set for a role (it applies company-wide), or give anyone a head-office role.
- To let another role see every branch (for example Finance or Operations manager), tick **See & manage every branch (head office)** for that role in Admin → Roles & permissions.

## Branding (logo, name and fonts)

Admin → Branding (needs "Manage integrations & keys"):

- **Logo:** upload a PNG, JPG or WEBP (up to 2 MB; a wide logo with a transparent background looks best). It appears in the website header and footer, the admin sidebar, the staff login, invoices and payslips, and as the browser tab icon. Untick "Show the name next to the logo" if the logo already contains your name.
- **Business name:** used across the website, admin, invoices and the apps' config.
- **Fonts:** pick a heading font and a text font from 22 Google Fonts. The live preview shows them before you save.

Uploaded logos are stored in `public/uploads/brand/` (keep this folder when you move servers). The apps receive the name and logo address in `/api/v1/app-config`; the app icon itself changes only with a new APK build.

## Drinking water reminders and watches (customer app)

Profile → 💧 Drink water (also a water card on the Today screen):

- Count glasses (goal 6–12 a day), see the last 7 days.
- Reminders every 1, 1½, 2 or 3 hours between the hours you choose, as local notifications. Settings stay on the phone; nothing is sent to the server.
- Each reminder has two buttons: **I drank a glass** and **In 30 min**.
- **Android watches (Wear OS: Galaxy Watch, Pixel Watch…)** and **Apple Watch** show the reminders, buttons included, through the watch's normal phone-notification mirroring. No separate watch app is needed. A tap on the watch is counted straight away if the app is running in the background, otherwise the next time the app opens.

## iPhone

The same app code runs on iPhone. In GitHub → Actions → **iPhone app** → Run workflow:

- **simulator**: builds the app for the iPhone Simulator (Xcode on a Mac). No Apple account needed. Download the artifact, unzip it and drag the `.app` onto a running Simulator.
- **device**: builds a real iPhone app on Expo EAS. Needs an Apple Developer account (99 USD a year), the `EXPO_TOKEN` repository secret, and a one-time `npx eas-cli build:configure` in `mobile/customer`.

## Wellness coach and daily workout (customer app)

- **Daily workout:** Profile → 🏃 Daily workout (and a card on Today). Log walk, run, yoga, gym, cycling, home workout, dance, sports or swimming with minutes and effort; daily goal, 🔥 streak, 7-day chart, a simple routine for each weekday, and one motivating reminder a day with "Did 20 min" / "In 1 hour" buttons (also on Wear OS and Apple Watch). "Eat for your workout" picks dishes from the menu for before and after exercise.
- **Smarter water nudges:** besides the regular reminders, the app checks at 3:30 PM and 7 PM and, only if you are behind, tells you how many glasses to drink now.
- **Wellness coach:** Profile → 🤖 Wellness coach. Off until the customer switches it on. Then the app sends daily totals (glasses, workout type / minutes / effort) to `PUT /api/v1/me/wellness`, and `GET /api/v1/me/coach` returns insights (behind on water, low weekly average, streaks), dishes from the next menu (before / after workout, hydrating, saltier ones with a water reminder) that respect diet and allergies, and a short message. With an OpenAI key in Admin → Integrations the message is written by AI from numbers and dish names only; without one, built-in rules write it. Switching it off deletes the stored days; deleting the account deletes them too.

## After uploading a new version

1. Upload **all** folders from the zip (including `public/`), replacing the old files. Keep your `.env`, `storage/` and `public/uploads/`.
2. Open Admin → System → **Back up and update now**.
3. Stylesheets load as `dt.css?v=…`, so browsers fetch the new design automatically. If the admin shows a red "styles did not load" banner, the server still has the old `public/css` — upload it again, then press Ctrl + Shift + R (Mac: Cmd + Shift + R). If you use LiteSpeed Cache or Cloudflare, purge its cache once.

## Breakfast, lunch, break time and dinner

- **Daily menu** (Admin → Daily menu): pick a day (14-day strip shows what's published per meal), pick a meal, tick dishes and set price and portions, then Save. "Quick copy" copies the same meal from yesterday, last week or any day/meal. **Meal times** (head office) switches meals on/off and sets each meal's order cut-off and delivery windows.
- **Add a dish** (Admin → Dishes → + Add a dish): photo, name, Veg/Egg/Non-veg, price and which meals it suits; nutrition is optional and can be left to the dietitian. The live preview shows how customers see it.
- **App:** the Menu tab has Breakfast / Lunch / Break time / Dinner tabs; one order is for one meal, with that meal's delivery windows.

## Dietitian desk

Admin → Dietitian desk (dietitian and doctor roles land here): dishes on the next 3 days' menus that still need review, everything else waiting, recently approved dishes, how many approved dishes suit each health need, and anonymous totals of what customers shared. The review page is one form: nutrition, salt level, ingredients, allergens, health needs, then Approve (and open the next dish).

## Dashboard

The Overview opens with "What to do today" (menus to publish, orders waiting, dishes almost sold out, dishes waiting for the dietitian, no rider on duty), one card per meal, four plain numbers, shortcuts, and "More details" with the charts. Pick a branch at the top (head office can choose All branches); the choice is remembered. Admin → Branding → **Dashboard font** sets one font for every staff screen (default Poppins).

## App login screen and Google Drive photos

- Admin → Branding → **Mobile app login screen**: up to 5 photos that slowly change, plus the headline and the line under it. The app loads them each time it opens.
- Admin → Integrations → **Google Drive**: add an OAuth client (Web application, Drive API enabled, redirect address shown on the page), press Connect Google Drive. New dish, banner, logo and login photos go to a "Daily Table photos" folder in that Drive, shared as view-only links. "Move existing photos to Drive" copies older photos. If Drive is unavailable, photos are saved on the server instead.

## Branches, menus and location (customer app)

- Every menu belongs to a branch kitchen. The app always shows the menu of the branch that delivers to the place it is serving, and the server refuses a basket from a branch that does not deliver to the chosen address.
- **Automatic nearest branch:** the Today screen uses, in order: an address the customer picked by hand; a saved address they are standing at; their current GPS location (nearest open branch connects automatically; they save the location as an address at checkout); their default address. It re-checks when the phone moves about 500 m or the app comes back to the front.
- **Several branches deliver there:** "Delivering from … · Change" lets the customer pick another branch; the choice is remembered for that address. Changing branch empties the basket.
- **No branch delivers there:** a clear "Sorry, our service is not available at your location yet" card with buttons to pick another address or use the current location.
- **Website:** the home page and area pages show each branch's own menu in tabs, grouped by meal.
