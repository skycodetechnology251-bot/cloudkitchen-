# Daily Table — website, admin and app backend

One Laravel 12 app serves:

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
9. **Admin → Zones, Kitchens, Dishes, Menus.** Draw your delivery areas, add kitchens and dishes, and publish menus.

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
- Staff bill for the kitchens they work in, set in Admin → Staff → Kitchens they work in. Admin and operations managers can bill for every kitchen and switch kitchens from the POS button's menu.
- Health details are visible only to dietitians and doctors.

**Not in this version:** the dietitian dashboard and AI-drafted diet plans from the designs. They are planned for the next phase. Their roles and the health-consent records are already in place.
