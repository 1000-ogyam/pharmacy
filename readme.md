# PL Pharma

Pharmacy wholesale and retail management for PL Pharmaceuticals (Ghana). Custom PHP 8.2 MVC, MySQL 8, single front controller.

## Requirements

- PHP 8.2+ with PDO MySQL, mbstring, openssl
- Composer
- MySQL 8.0 (XAMPP is fine)
- Apache `mod_rewrite` (or `php -S` for local)

## Setup

```bash
composer install
copy .env.example .env
```

Edit `.env`:

```
APP_BASE_PATH=/pharmacy
APP_URL=http://localhost/pharmacy
DB_HOST=127.0.0.1
DB_NAME=plpharmacore
DB_USER=root
DB_PASS=
```

If you use the PHP built-in server instead of XAMPP, set `APP_BASE_PATH=/` and `APP_URL=http://localhost:8000`.

## Live (Hostinger / pharmacy.eljira.com)

The site is at the domain root, not `/pharmacy`. In `.env` on the server:

```
APP_ENV=production
APP_DEBUG=false
APP_BASE_PATH=/
APP_URL=https://pharmacy.eljira.com
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_user
DB_PASS=your_password
```

Then in hPanel:

1. Set the domain PHP version to **8.2** or **8.3**.
2. SSH (or hPanel terminal) in the project folder and run `composer install --no-dev --optimize-autoloader`.
3. Point the document root at `public` if the panel allows it; otherwise leave the project in `public_html` and keep the root `.htaccess`.
4. Run `php database/migrate.php` (and `php database/seed.php` only if you want demo data).
5. Make `storage/logs`, `storage/cache`, and `public/uploads` writable.

Create schema and demo data:

```bash
php database/migrate.php
php database/seed.php
```

`migrate.php` creates the database if it does not exist.

## Run

**XAMPP:** start Apache + MySQL, then open [http://localhost/pharmacy/](http://localhost/pharmacy/)

**Built-in server:**

```bash
php -S localhost:8000 -t public
```

## Demo logins

Password for all users: `Password123!`

| Role | Email |
|---|---|
| Admin | admin@plpharma.com |
| Manager | manager@plpharma.com |
| Cashier | cashier@plpharma.com |
| Pharmacist | pharmacist@plpharma.com |
| Warehouse | warehouse@plpharma.com |
| Finance | finance@plpharma.com |
| Wholesale | wholesale@plpharma.com |

**Staff how-to:** in the app open **Help**, or see [USER_GUIDE.md](USER_GUIDE.md).

## Cron (run locally)

```bash
php cron/process_sms_queue.php
php cron/check_expiring_batches.php
php cron/send_daily_summary.php
```

On a server, schedule those the same way (every minute for the SMS queue, daily for the others).

## SMS (Arkesel)

Set these in `.env`:

```
SMS_GATEWAY=arkesel
SMS_API_KEY=your_arkesel_api_key
SMS_SENDER_ID=PLPharma
SMS_SANDBOX=false
```

`SMS_SENDER_ID` must be a sender ID already approved in the Arkesel dashboard. Leave `SMS_API_KEY` empty to simulate sends without calling the API. Queue a message from **SMS**, then run `php cron/process_sms_queue.php` or click **Send queued messages**.

## What is in this build

Working modules from the build spec sequence:

1. Foundation — routing, auth/RBAC, CSRF, layouts, audit log
2. Products, units, prices, batches, FEFO, stock per branch
3. Retail POS, payments, receipts, expired/recalled block
4. Wholesale quotations → orders → invoices and credit limits
5. Suppliers, purchase orders, verified GRN (stock rises only then)
6. Cashbook, ledger, AR collections, simple P&L
7. SMS templates + DB queue via Arkesel
8. Prescriptions, dispensing, NHIS claims
9. Deliveries, returns, staff/licence tracking, USSD webhook, portals
10. GRA e-invoice placeholder IRN
11. Sales and product reports plus role dashboards

See `DECISIONS.md` for choices not pinned in the original spec. The original AI build prompt is in `readme.md`.
