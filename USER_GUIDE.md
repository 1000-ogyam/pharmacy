# PL Pharma — User manual

This guide is also in the app: open **Help** in the menu, or go to `/help` (live: [https://pharmacy.eljira.com/help](https://pharmacy.eljira.com/help)). That page has jump links and accordions.

This markdown copy is for printing or sharing outside the system.

This guide is for staff who sell, dispense, buy, deliver, or account for medicines in **PL Pharma**. Amounts are in **Ghana cedi (GHS)**.

Live site: [https://pharmacy.eljira.com/](https://pharmacy.eljira.com/)  
Local (XAMPP): [http://localhost/pharmacy/](http://localhost/pharmacy/)

---

## 1. Sign in and the workspace

1. Open the site. You should see **Sign in**.
2. Enter the email and password your administrator gave you.
3. Click **Sign in**.

After login you land on **Dashboard**. The top bar shows your **branch** and your name. Use **Sign out** when you leave the counter.

**If sign-in fails**

- Check email spelling and caps in the password.
- After several failed tries the account can lock for a short time.
- A red banner about the database means the server is not connected to MySQL — an administrator must fix `.env` and run migrations (see the setup README), not a counter issue.

**Screen habits**

- Most **New** / **Add** / **Edit** actions open a **popup**. Fill the form and save. Click the X or outside the popup to cancel.
- Destructive actions (convert a quote, recall a batch, mark delivered) ask you to **confirm**.
- Lists show **10 rows per page**. Use the pager at the bottom.
- You only see data for **your branch**. You cannot open another branch’s sales or stock.
- On a small screen, tap the menu button (top left) to open the sidebar.

---

## 2. Who can do what

Your menu depends on your role.

| Role | Typical work | Main menu |
|---|---|---|
| **Admin** | Full access | All modules |
| **Cashier** | Retail counter | POS, Customers, My sales |
| **Pharmacist** | Dispensing | POS, Prescriptions, NHIS, Batches, Patients |
| **Warehouse** | Buying and stock | Stock, Batches, Products, Purchase orders, Suppliers, Returns |
| **Finance** | Money and credit | Accounting, Credit / AR, Reports, Customers, Suppliers, Approvals |
| **Wholesale** | Account customers | Orders, Accounts, Credit limits, Deliveries, Catalogue |
| **Customer** (portal) | Own invoices | Customer portal |
| **Supplier** (portal) | Own purchase orders | Supplier portal |

Demo accounts (password for all: `Password123!`) exist only where demo data was seeded:

| Role | Email |
|---|---|
| Admin | admin@plpharma.com |
| Cashier | cashier@plpharma.com |
| Pharmacist | pharmacist@plpharma.com |
| Warehouse | warehouse@plpharma.com |
| Finance | finance@plpharma.com |
| Wholesale | wholesale@plpharma.com |

Change these passwords before live use.

---

## 3. Dashboard

The dashboard is a morning snapshot for your branch:

- **Today’s sales** and transaction count
- **This month** gross sales
- **Low stock** (at or below reorder level)
- **Expiring in 90 days** — these batches will be sold first (FEFO)
- **Recent sales**
- **Expiry and recall alerts**

Cashiers and pharmacists can open **POS** from the large button.

---

## 4. Retail POS (cashiers, pharmacists, admin)

**Sales → Retail POS** (or **POS** on the cashier/pharmacist menu).

### Ring up a sale

1. Search by name, SKU, barcode, or generic name, or scroll the catalogue.
2. **Tap a product** to add it to the cart. Use grid or list view if you prefer.
3. Adjust quantity on the cart line if needed.
4. Choose a **customer**, or leave **Walk-in**.
5. Choose **payment**: cash, mobile money, card, or credit.
6. Optional: enter a **discount** in GHS (not a percentage).
7. Click **Checkout**.

You are taken to the **receipt**. Use **Print**, then **New sale** for the next customer.

### Rules the till already enforces

- The system picks the batch that **expires soonest** (FEFO). You do not choose the batch.
- **Expired** and **recalled** stock cannot be sold.
- If there is not enough sellable stock, checkout stops and shows an error. Do not override it.
- **Credit** needs a saved customer with enough unused credit limit. Walk-in credit will fail.
- The till price is the **retail price from the system**. Changing it in the browser does not change what is charged.

### After the sale

- Stock on that batch goes down immediately.
- If the customer opted in to SMS, a receipt message can be queued (it goes out when SMS is processed).
- Finance can still open the receipt later from reports or the sale number.

---

## 5. Customers and credit

**Sales → Customers** (pharmacists see this as **Patients**).

### Add or edit a customer

Click **New customer** (popup). Fill:

- **Name** (required)
- **Type**: retail or wholesale
- Phone, email, address
- **NHIS number** if they claim on NHIS
- **Credit limit** (GHS) for account sales
- **Pricing tier** for wholesale (tier 1 or 2)
- **SMS opt-in** — uncheck if they should not get marketing/receipt SMS (transactional notices may still be queued)

Save. Edit any row the same way.

### Collect on account

**Sales → Credit** (finance: **Credit / AR**).

Each wholesale/retail account shows limit, outstanding **balance**, and **available** credit.

1. Click **Collect**.
2. Enter the amount and method (cash, MoMo, bank).
3. **Apply payment**.

The balance falls and the cashbook/ledger is updated. Wholesale convert will refuse a quote if the new invoice would exceed the limit.

---

## 6. Wholesale (wholesale staff, admin)

**Sales → Wholesale**.

Flow: **quotation → convert → order + invoice** (credit checked on convert).

### Create a quotation

1. Click **New quotation**.
2. Select the **wholesale customer**.
3. Click **Add line**, pick a product, enter quantity. The **in stock** figure is a guide.
4. Add more lines as needed. Save.

The quote sits as open until you convert it.

### Convert to an order

1. On the quotation row, click **Convert**.
2. Confirm.

The system checks **sellable stock** and **credit limit**. If either fails, you get an error and the quote stays open — it does not create a half order.

On success you get a wholesale **order number** and an **invoice**. Stock is taken with FEFO the same way as retail.

### Deliveries

**Operations → Deliveries**.

1. **Schedule delivery**: customer, driver, address, date/time.
2. When the drop is done, click **Delivered** and confirm.

This tracks the trip; it does not by itself reverse stock.

---

## 7. Products and inventory (warehouse, admin; wholesale can view catalogue)

Stock **does not go up** when you save a product or a purchase order. It goes up only after a **verified goods receipt**.

### Products

**Inventory → Products**.

**New product** needs SKU, name, and a **retail price**. You can also set wholesale price, unit name, generic name, category, dosage form, strength, manufacturer, barcode, and flags:

- **Requires prescription**
- **Controlled**

Use **Edit** to change details later. Wholesale staff can open the catalogue but buying is warehouse/admin.

### Stock levels

**Inventory → Stock levels**.

On-hand quantity for this branch, reorder level, and status: **OK**, **Low**, or **Out**.

**Transfer stock** (admin/warehouse): choose destination branch, product, and quantity. FEFO takes stock from *your* branch and moves it. You cannot transfer more than you have.

### Batches

**Inventory → Batches**.

Each receipt creates (or tops up) a batch with number, expiry, and remaining qty.

| Status | Meaning |
|---|---|
| Sellable | Can go on a ticket |
| Expiring | Within 60 days — sell first |
| Expired | Cannot sell |
| Recalled | Blocked from sale |

**Recall** a batch only when it must not be sold (quality issue, regulator notice). Confirm the popup. Recalled quantity stays on the batch record but POS will refuse it.

---

## 8. Buying: suppliers and purchase orders

### Suppliers

**Inventory → Suppliers**. Add name, contact, phone, email, address, currency, and payment terms.

### Purchase orders

**Inventory → Purchase orders**.

1. **New purchase order**: supplier, currency, exchange rate to GHS.
2. **Add line**: product, quantity, unit cost.
3. Save. Status is open; **stock has not changed yet**.

### Receive goods (GRN)

Open the PO (or use the receive action).

For each line enter:

- **Receive now** (quantity arriving today)
- **Batch number**
- **Expiry date**

Click **Verify goods receipt**.

Only then does on-hand stock rise, and a batch becomes sellable (if not expired). You can receive in parts until the ordered quantity is complete.

---

## 9. Prescriptions and NHIS (pharmacists, admin)

### Capture a prescription

**Clinical → Prescriptions** → **New prescription**.

1. Select the **patient** (create them under Customers first if needed).
2. Enter who prescribed it.
3. **Add line**: product, dosage text, quantity.
4. Save. You get a prescription number (status recorded, not yet dispensed).

### Dispense

Open the prescription → **Dispense via POS / FEFO**.

Choose payment: **cash**, **NHIS**, or **credit**. This creates a sale, takes FEFO stock, and marks the prescription dispensed. Same stock rules as the till.

### NHIS claims

**Clinical → NHIS claims**.

1. **Submit claim**.
2. Choose the prescription and the amount claimed.
3. Save. The claim is stored with a claim number and status.

This is an **in-house register** for follow-up. It does not file electronically to NHIS.

---

## 10. Returns (warehouse, pharmacist, admin)

**Operations → Returns**.

1. **Create return**.
2. Select the original **sale** and a reason.
3. Save. Stock is restored to the batches that were sold.

Use this for genuine customer or quality returns, not to “fix” a wrong till total without a sale.

---

## 11. Approvals (admin, finance)

**Operations → Approvals**.

Pending requests show type, record, and status. Click **Approve** and confirm.

If the list is empty, there is nothing waiting.

---

## 12. Staff and licences (admin only)

**Operations → Staff**.

**Add staff**: name, email, password, **role**, **branch**, optional pharmacist licence number and expiry.

Staff with a licence due within 60 days show a **Renew** badge. A user only works in the branch you assign here.

---

## 13. Accounting and reports

### Accounting (admin, finance)

**Finance → Accounting**.

- Cash in / cash out (cashbook)
- Accounts receivable
- Simple P&L from the ledger
- Recent ledger lines (sales, COGS, collections, and so on)

You do not type journal entries for normal sales; the till, wholesale convert, GRN, and collections post them.

### Reports (most staff)

**Finance → Reports** (cashiers: **My sales**).

Pick **from** and **to** dates, then **Filter**.

- **Daily sales** — count and total per day
- **Top products** — quantity and value in the range

Figures follow your branch.

---

## 14. SMS (admin)

**Finance → SMS**.

Messages are **queued** first, then sent through **Arkesel**.

1. **Queue SMS**: Ghana number (`0244…` or `233…`) and text, or rely on templates (receipt, invoice, OTP, expiry alert, and so on).
2. **Send queued messages**, or wait for the scheduled job.

The page shows gateway, sender ID, and whether an API key is set. With no key, sends are **simulated** (marked sent locally, no real SMS). Receipt SMS only goes to customers with **SMS opt-in**, except purely transactional templates.

---

## 15. Customer and supplier portals

External accounts are created by an administrator (staff record linked to a customer or supplier).

- **Customer portal** — that account’s invoices, amounts, due dates, status.
- **Supplier portal** — that supplier’s purchase orders and totals.

They cannot see other accounts’ documents.

---

## 16. Suggested daily rhythm

**Open**

1. Sign in. Check Dashboard: low stock, expiry alerts, today’s sales.
2. Cashiers: open POS. Pharmacists: check prescriptions waiting to dispense.
3. Warehouse: review POs due and batches marked expiring.

**During the day**

- Sell on POS; print receipts.
- Wholesale: quote, convert only when stock and credit allow, schedule deliveries.
- Receive goods the same day they arrive (batch + expiry on the GRN).
- Collect account payments as cash comes in.

**Close**

1. Finance: Credit collections vs cashbook; glance at P&L.
2. Warehouse: recall any unsafe batch immediately.
3. Everyone: **Sign out**.

---

## 17. Common messages

| You see | What to do |
|---|---|
| Invalid credentials or account locked | Recheck password; wait if locked; ask admin to reset. |
| Insufficient sellable stock | Receive stock or reduce qty. Expired/recalled batches do not count. |
| Convert / credit error | Lower the quote, collect AR, or raise the customer’s credit limit. |
| Session expired / CSRF | Refresh the page and try again. Do not leave a form open for hours. |
| Cannot connect to the database | Administrator: Hostinger DB name/user/password in `.env`. |
| Database tables are missing | Administrator: `php database/migrate.php` then `php database/seed.php` if you need demo users. |
| 403 / no access | Wrong role for that screen. Use the account meant for that job. |
| Product not on POS | Inactive product, no sellable batch, or you are on the wrong branch. |

---

## 18. What this system does not do yet

Treat these as **manual / later** work, not missing buttons:

- GRA e-invoice (IRN) is a placeholder — keep GRA filing in your usual process.
- NHIS is a local claim list, not a live NHIA submission.
- Demand forecasting, anomaly detection, and route optimisation are not in this build.

---

## 19. Need a new login?

Ask an **administrator** to add you under **Staff** with the correct **role** and **branch**. Do not share till passwords. Sign out on shared PCs.
