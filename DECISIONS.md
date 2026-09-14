# Implementation decisions

Choices made where the build spec left room, so later work stays consistent.

## Project root

The workspace is `C:\xampp1\htdocs\pharmacy`, so the application lives at this root rather than a nested `plpharmacore/` folder. Structure otherwise matches Section 3.

## Hosting path

Default local `.env` uses `APP_BASE_PATH=/pharmacy`. Live (Hostinger subdomain at `https://pharmacy.eljira.com/`) must use `APP_BASE_PATH=/`. Root `.htaccess` fronts `public/` with no hardcoded RewriteBase so LiteSpeed does not rewrite-loop. PHP's built-in server (`php -S localhost:8000 -t public`) should set `APP_BASE_PATH=/`.

Session cookies use path `/` so either URL style keeps the login session.

## Query layer

No ORM. `Model` + a small `QueryBuilder` cover find/where/create/update/soft-delete and audit hooks. Join-heavy screens use `Database` with prepared statements.

## SMS package layout

The spec lists `app/Services/SmsService.php`. The contract and gateways live under `app/Services/Sms/` so each gateway is one class. `App\Services\SmsService::make()` is the factory controllers can call.

Without an API key, gateways record a simulated send so the queue/cron path is demoable offline. Live sending uses Arkesel `POST https://sms.arkesel.com/api/v2/sms/send` with the `api-key` header.

## Resource middleware

`$router->resource()->middleware()` applies the middleware to every generated resource route, not only the last one.

## Accounting

Chart of accounts is a short Ghana-friendly set (cash, AR, inventory, AP, sales, COGS). POS posts cash/AR and sales; historical reports use the stored PO exchange rate, never a live FX feed.

## GRA / NHIS / USSD

`GraInvoiceService` issues a local IRN placeholder. `NhisClaimService` records a submitted claim. USSD keeps session state in `ussd_sessions` and does not use PHP sessions.

## Portals

Customer and supplier portals reuse staff auth plus `customer` / `supplier` roles. They are read-oriented demo surfaces, not a separate identity provider.

## Phase 3

Forecasting, anomaly detection, and route optimization are not implemented. They need history from the earlier modules first.

## Demo credentials

All seeded staff users share `Password123!`. Admin: `admin@plpharma.com`.
