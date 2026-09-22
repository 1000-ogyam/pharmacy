<?php
$sections = [
    ['id' => 'sign-in', 'title' => 'Sign in & workspace'],
    ['id' => 'roles', 'title' => 'Who can do what'],
    ['id' => 'dashboard', 'title' => 'Dashboard'],
    ['id' => 'pos', 'title' => 'Retail POS'],
    ['id' => 'customers', 'title' => 'Customers & credit'],
    ['id' => 'wholesale', 'title' => 'Wholesale'],
    ['id' => 'inventory', 'title' => 'Products & stock'],
    ['id' => 'buying', 'title' => 'Suppliers & purchase orders'],
    ['id' => 'prescriptions', 'title' => 'Prescriptions & NHIS'],
    ['id' => 'returns', 'title' => 'Returns'],
    ['id' => 'approvals', 'title' => 'Approvals'],
    ['id' => 'staff', 'title' => 'Staff & licences'],
    ['id' => 'accounting', 'title' => 'Accounting & reports'],
    ['id' => 'sms', 'title' => 'SMS'],
    ['id' => 'portals', 'title' => 'Customer & supplier portals'],
    ['id' => 'daily', 'title' => 'Daily rhythm'],
    ['id' => 'messages', 'title' => 'Common messages'],
    ['id' => 'limits', 'title' => 'What this build does not do'],
];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Manual</p>
        <h2>How to use PL PharmaCore</h2>
        <p>Jump to a topic, or open a section. Amounts are in Ghana cedi (GHS). You only work in your own branch.</p>
    </div>
    <div class="help-toolbar no-print">
        <button type="button" class="btn btn-outline btn-sm" data-help-expand>Expand all</button>
        <button type="button" class="btn btn-outline btn-sm" data-help-collapse>Collapse all</button>
    </div>
</div>

<nav class="help-toc" aria-label="Guide topics">
    <?php foreach ($sections as $section): ?>
        <a href="#<?= e($section['id']) ?>"><?= e($section['title']) ?></a>
    <?php endforeach; ?>
</nav>

<details class="help-acc" id="sign-in">
    <summary>Sign in and the workspace</summary>
    <div class="help-acc-body">
        <ol>
            <li>Open the site and enter the email and password your administrator gave you.</li>
            <li>Click <strong>Sign in</strong>. You land on Dashboard. The top bar shows your branch and name.</li>
            <li>Use <strong>Sign out</strong> when you leave the counter.</li>
            <li>Use <strong>Click to install app</strong> to pin the workspace to a phone home screen or computer desktop.</li>
        </ol>
        <h3>If sign-in fails</h3>
        <ul>
            <li>Check email spelling and password. Several failed tries can lock the account briefly.</li>
            <li>A red banner about the database is a server setup issue, not a till issue.</li>
        </ul>
        <h3>Screen habits</h3>
        <ul>
            <li>Most <strong>New</strong> / <strong>Add</strong> / <strong>Edit</strong> actions open a popup. Save, or click X to cancel.</li>
            <li>Convert, recall, and mark-delivered ask you to confirm.</li>
            <li>Lists show 10 rows per page.</li>
            <li>On a phone, tap the menu button (top left) for the sidebar.</li>
        </ul>
        <p class="help-jump"><a class="btn btn-outline btn-sm" href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
</details>

<details class="help-acc" id="roles">
    <summary>Who can do what</summary>
    <div class="help-acc-body">
        <p>Your menu depends on your role.</p>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Role</th><th>Typical work</th><th>Main menu</th></tr></thead>
                <tbody>
                    <tr><td>Admin</td><td>Full access</td><td>All modules including SMS</td></tr>
                    <tr><td>Manager</td><td>Branch leadership</td><td>Operations, staff, reports (no SMS)</td></tr>
                    <tr><td>Cashier</td><td>Retail counter</td><td>POS, Customers, My sales</td></tr>
                    <tr><td>Pharmacist</td><td>Dispensing</td><td>POS, Prescriptions, NHIS, Batches, Patients</td></tr>
                    <tr><td>Warehouse</td><td>Buying and stock</td><td>Stock, Batches, Products, Purchase orders, Suppliers, Returns</td></tr>
                    <tr><td>Finance</td><td>Money and credit</td><td>Accounting, Credit / AR, Reports, Approvals</td></tr>
                    <tr><td>Wholesale</td><td>Account customers</td><td>Orders, Accounts, Credit limits, Deliveries, Catalogue</td></tr>
                    <tr><td>Customer</td><td>Own invoices</td><td>Customer portal</td></tr>
                    <tr><td>Supplier</td><td>Own purchase orders</td><td>Supplier portal</td></tr>
                </tbody>
            </table>
        </div>
        <p>Demo password (only where demo data was seeded): <code>Password123!</code> — change it before live use.</p>
    </div>
</details>

<details class="help-acc" id="dashboard">
    <summary>Dashboard</summary>
    <div class="help-acc-body">
        <p>A morning snapshot for your branch: today’s sales, this month, low stock, batches expiring in 90 days, recent sales, and expiry/recall alerts. FEFO will prefer near-expiry batches first.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/dashboard')) ?>">Open Dashboard</a></p>
    </div>
</details>

<details class="help-acc" id="pos">
    <summary>Retail POS</summary>
    <div class="help-acc-body">
        <p>Cashiers, pharmacists, and admin. Open <strong>Retail POS</strong>.</p>
        <h3>Ring up a sale</h3>
        <ol>
            <li>Search by name, SKU, barcode, or generic name, or tap a product in the catalogue.</li>
            <li>Adjust quantity on the cart. Choose a customer or leave <strong>Walk-in</strong>.</li>
            <li>Payment: cash, mobile money, card, or credit. Optional discount is in GHS, not a percentage.</li>
            <li>Checkout, then <strong>Print</strong> the receipt. <strong>New sale</strong> starts the next ticket.</li>
        </ol>
        <h3>Rules the till already enforces</h3>
        <ul>
            <li>The system picks the batch that expires soonest (FEFO). You do not choose the batch.</li>
            <li>Expired and recalled stock cannot be sold.</li>
            <li>Not enough sellable stock stops checkout — do not try to override it.</li>
            <li>Credit needs a saved customer with unused credit. Walk-in credit will fail.</li>
            <li>The charged price is the retail price from the system.</li>
        </ul>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/pos')) ?>">Open POS</a></p>
    </div>
</details>

<details class="help-acc" id="customers">
    <summary>Customers and credit</summary>
    <div class="help-acc-body">
        <p>Open <strong>Customers</strong> (pharmacists see <strong>Patients</strong>).</p>
        <h3>Add or edit</h3>
        <p>Name is required. Set type (retail or wholesale), phone, email, NHIS number, credit limit, wholesale pricing tier, and SMS opt-in.</p>
        <h3>Collect on account</h3>
        <p>Open <strong>Credit</strong>. Click <strong>Collect</strong>, enter amount and method (cash, MoMo, bank), then apply. The balance falls and the cashbook updates. Wholesale convert will refuse a quote that would exceed the limit.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/customers')) ?>">Customers</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/credit')) ?>">Credit</a>
        </p>
    </div>
</details>

<details class="help-acc" id="wholesale">
    <summary>Wholesale</summary>
    <div class="help-acc-body">
        <p>Flow: quotation → convert → order and invoice. Credit is checked on convert.</p>
        <ol>
            <li><strong>New quotation</strong>: pick the wholesale customer, <strong>Add line</strong> for product and qty, then save.</li>
            <li><strong>Convert</strong> on the quote row and confirm. The system checks sellable stock and credit limit. If either fails, the quote stays open — no half order.</li>
            <li>On success you get an order number and invoice. Stock is taken with FEFO.</li>
        </ol>
        <h3>Deliveries</h3>
        <p>Schedule customer, driver, address, and time. When the drop is done, click <strong>Delivered</strong>. This tracks the trip; it does not reverse stock.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/wholesale')) ?>">Wholesale</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/deliveries')) ?>">Deliveries</a>
        </p>
    </div>
</details>

<details class="help-acc" id="inventory">
    <summary>Products and stock</summary>
    <div class="help-acc-body">
        <p>Stock does <strong>not</strong> go up when you save a product or a purchase order. It goes up only after a verified goods receipt.</p>
        <h3>Products</h3>
        <p>New product needs SKU, name, and retail price. You can set wholesale price, barcode, generic name, and flags for prescription-only or controlled items.</p>
        <h3>Stock levels</h3>
        <p>On-hand quantity, reorder level, and status: OK, Low, or Out. <strong>Transfer stock</strong> moves FEFO quantity to another branch. You cannot transfer more than you have.</p>
        <h3>Batches</h3>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td>Sellable</td><td>Can go on a ticket</td></tr>
                    <tr><td>Expiring</td><td>Within 60 days — sell first</td></tr>
                    <tr><td>Expired</td><td>Cannot sell</td></tr>
                    <tr><td>Recalled</td><td>Blocked from sale</td></tr>
                </tbody>
            </table>
        </div>
        <p>Recall a batch only when it must not be sold. Recalled quantity stays on the record but POS will refuse it.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/products')) ?>">Products</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/inventory')) ?>">Stock</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/inventory/batches')) ?>">Batches</a>
        </p>
    </div>
</details>

<details class="help-acc" id="buying">
    <summary>Suppliers and purchase orders</summary>
    <div class="help-acc-body">
        <ol>
            <li>Add the <strong>supplier</strong> (contact, currency, payment terms).</li>
            <li><strong>New purchase order</strong>: supplier, currency, exchange rate to GHS, then lines (product, qty, unit cost). Status is open — stock has not changed.</li>
            <li>Open the PO and <strong>Verify goods receipt</strong>. For each line enter quantity arriving, batch number, and expiry date.</li>
        </ol>
        <p>Only then does on-hand stock rise. You can receive in parts until the ordered quantity is complete.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/suppliers')) ?>">Suppliers</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/purchase-orders')) ?>">Purchase orders</a>
        </p>
    </div>
</details>

<details class="help-acc" id="prescriptions">
    <summary>Prescriptions and NHIS</summary>
    <div class="help-acc-body">
        <h3>Capture</h3>
        <p>Create the patient under Customers if needed. New prescription: patient, prescriber, then lines (product, dosage, qty). You get a prescription number; it is not dispensed yet.</p>
        <h3>Dispense</h3>
        <p>Open the prescription → <strong>Dispense via POS / FEFO</strong>. Payment: cash, NHIS, or credit. This creates a sale and takes stock. Same stock rules as the till.</p>
        <h3>NHIS claims</h3>
        <p>Submit claim against a prescription and amount. This is an in-house register — it does not file electronically to NHIS.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/prescriptions')) ?>">Prescriptions</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/nhis')) ?>">NHIS</a>
        </p>
    </div>
</details>

<details class="help-acc" id="returns">
    <summary>Returns</summary>
    <div class="help-acc-body">
        <p>Create return, select the original sale and a reason. Stock is restored to the batches that were sold. Use this for genuine returns, not to patch a wrong till total without a sale.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/returns')) ?>">Returns</a></p>
    </div>
</details>

<details class="help-acc" id="approvals">
    <summary>Approvals</summary>
    <div class="help-acc-body">
        <p>Admin and finance. Pending requests show type and record. Click <strong>Approve</strong> and confirm. An empty list means nothing is waiting.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/approvals')) ?>">Approvals</a></p>
    </div>
</details>

<details class="help-acc" id="staff">
    <summary>Staff and licences</summary>
    <div class="help-acc-body">
        <p>Admin and manager. <strong>Add staff</strong> or <strong>Edit</strong> opens a popup: name, email, phone, password (optional when editing), role, branch, licence, portal customer/supplier links, and active status. Email, phone, and portal links must be unique among active staff. Use <strong>Delete</strong> to archive an account (not your own). Managers cannot create or edit administrator or portal-only logins. A <strong>Renew</strong> badge appears when a licence is due within 60 days.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/staff')) ?>">Staff</a></p>
    </div>
</details>

<details class="help-acc" id="accounting">
    <summary>Accounting and reports</summary>
    <div class="help-acc-body">
        <p><strong>Accounting</strong> shows cash in/out, receivables, a simple P&amp;L, and recent ledger lines. Normal sales, GRN, wholesale convert, and collections post themselves — you do not type those journals.</p>
        <p><strong>Reports</strong>: pick from and to dates. Daily sales and top products follow your branch. Cashiers see this as <strong>My sales</strong>.</p>
        <p class="help-jump">
            <a class="btn btn-sm" href="<?= e(url('/accounting')) ?>">Accounting</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/reports')) ?>">Reports</a>
        </p>
    </div>
</details>

<details class="help-acc" id="sms">
    <summary>SMS</summary>
    <div class="help-acc-body">
        <p>Admin. Messages are queued, then sent through Arkesel. Queue a Ghana number (<code>0244…</code> or <code>233…</code>) or use templates, then <strong>Send queued messages</strong>.</p>
        <p>With no API key, sends are simulated. Receipt SMS goes to customers with SMS opt-in.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/sms')) ?>">SMS</a></p>
    </div>
</details>

<details class="help-acc" id="portals">
    <summary>Customer and supplier portals</summary>
    <div class="help-acc-body">
        <p>An administrator creates the login and links it to a customer or supplier. The customer portal shows that account’s invoices. The supplier portal shows that supplier’s purchase orders. They cannot see other accounts.</p>
        <p class="help-jump">
            <a class="btn btn-outline btn-sm" href="<?= e(url('/portal')) ?>">Customer portal</a>
            <a class="btn btn-outline btn-sm" href="<?= e(url('/supplier-portal')) ?>">Supplier portal</a>
        </p>
    </div>
</details>

<details class="help-acc" id="daily">
    <summary>Suggested daily rhythm</summary>
    <div class="help-acc-body">
        <h3>Open</h3>
        <p>Sign in. Check Dashboard (low stock, expiry, today’s sales). Cashiers open POS. Pharmacists check prescriptions waiting. Warehouse reviews POs and expiring batches.</p>
        <h3>During the day</h3>
        <p>Sell on POS. Wholesale: quote, convert only when stock and credit allow, schedule deliveries. Receive goods the same day they arrive. Collect account payments as cash comes in.</p>
        <h3>Close</h3>
        <p>Finance: collections vs cashbook. Warehouse: recall any unsafe batch immediately. Everyone: Sign out.</p>
    </div>
</details>

<details class="help-acc" id="messages">
    <summary>Common messages</summary>
    <div class="help-acc-body">
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>You see</th><th>What to do</th></tr></thead>
                <tbody>
                    <tr><td>Invalid credentials or account locked</td><td>Recheck password; wait if locked; ask admin to reset.</td></tr>
                    <tr><td>Insufficient sellable stock</td><td>Receive stock or reduce qty. Expired/recalled batches do not count.</td></tr>
                    <tr><td>Convert / credit error</td><td>Lower the quote, collect AR, or raise the credit limit.</td></tr>
                    <tr><td>Session expired</td><td>Refresh and try again. Do not leave a form open for hours.</td></tr>
                    <tr><td>Cannot connect to the database</td><td>Administrator: check DB settings in <code>.env</code>.</td></tr>
                    <tr><td>Database tables are missing</td><td>Administrator: run migrations (and seed if you need demo users).</td></tr>
                    <tr><td>403 / no access</td><td>Wrong role for that screen.</td></tr>
                    <tr><td>Product not on POS</td><td>Inactive product, no sellable batch, or wrong branch.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</details>

<details class="help-acc" id="limits">
    <summary>What this build does not do</summary>
    <div class="help-acc-body">
        <ul>
            <li>GRA e-invoice (IRN) is a placeholder — keep GRA filing in your usual process.</li>
            <li>NHIS is a local claim list, not a live NHIA submission.</li>
            <li>Demand forecasting, anomaly detection, and route optimisation are not in this build.</li>
        </ul>
        <p>Need a new login? Ask an administrator to add you under Staff with the correct role and branch. Do not share till passwords.</p>
    </div>
</details>
