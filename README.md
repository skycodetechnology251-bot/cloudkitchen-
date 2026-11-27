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
