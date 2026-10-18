<?php
$sections = [
    ['id' => 'sign-in', 'title' => 'Sign in & workspace'],
    ['id' => 'roles', 'title' => 'Who can do what'],
    ['id' => 'dashboard', 'title' => 'Dashboard'],
    ['id' => 'pos', 'title' => 'Retail POS'],
    ['id' => 'sales-history', 'title' => 'Sales history'],
    ['id' => 'archives', 'title' => 'Archives'],
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
        <h2>How to use <?= e(config('app.name')) ?></h2>
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
            <li>Most <strong>New</strong> / <strong>Add</strong> / <strong>Edit</strong> / <strong>View</strong> actions open a <strong>popup</strong>. Scroll inside the popup if the form is long; Save/Cancel stay at the bottom.</li>
            <li>Convert, archive, recall, and mark-delivered ask you to confirm.</li>
            <li>Lists show 10 rows per page. Tables use compact rows where many actions appear on one line.</li>
            <li>On a phone, tap the menu button (top left) for the sidebar. Wide popups use the full screen on small devices.</li>
            <li><strong>Print</strong> on receipts and wholesale orders opens a simple print page (not the whole app menu).</li>
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
                    <tr><td>Admin</td><td>Full control</td><td>Same menu as all staff, plus delete records and manage staff accounts</td></tr>
                    <tr><td>Manager, Cashier, Pharmacist, Medicine Counter Assistant, Warehouse, Accountant, Wholesale</td><td>Day-to-day operations</td><td>Full admin menu: create and edit products, sales, customers, stock, prescriptions, finance, SMS, and more</td></tr>
                    <tr><td>All non-admin staff</td><td>Restrictions</td><td>Cannot delete or archive records. Cannot add, edit, or delete staff users (view staff list only)</td></tr>
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
        <p>A morning snapshot for your branch: today’s and this month’s <strong>completed</strong> sales (returned tickets are excluded), low stock, batches expiring in 90 days, recent sales, and expiry/recall alerts. FEFO will prefer near-expiry batches first.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/dashboard')) ?>">Open Dashboard</a></p>
    </div>
</details>

<details class="help-acc" id="pos">
    <summary>Retail POS</summary>
    <div class="help-acc-body">
        <p>Cashiers, pharmacists, and admin. Open <strong>Retail POS</strong>.</p>
        <h3>Ring up a sale</h3>
        <ol>
            <li>Choose <strong>Retail prices</strong> or <strong>Wholesale prices</strong> at the top before adding products (catalogue shows both for reference).</li>
            <li>Search by name, SKU, barcode, or generic name, or tap a product. Stock shown is <strong>sellable</strong> quantity at your branch.</li>
            <li>Adjust quantity on the cart. Choose a customer or leave <strong>Walk-in</strong>.</li>
            <li>Payment: cash, mobile money, card, or credit. Optional discount is in GHS, not a percentage.</li>
            <li>Checkout opens the receipt page. Click <strong>Print</strong> — only the receipt prints (sidebar and buttons are hidden). Use <strong>New sale</strong> for the next ticket.</li>
        </ol>
        <h3>Rules the till already enforces</h3>
        <ul>
            <li>The system picks the batch that expires soonest (FEFO). You do not choose the batch.</li>
            <li>Checkout <strong>reduces stock</strong> on those batches and updates branch totals automatically.</li>
            <li>Expired and recalled stock cannot be sold.</li>
            <li>Not enough sellable stock stops checkout — do not try to override it.</li>
            <li>Credit needs a saved customer with unused credit. Walk-in credit will fail.</li>
            <li>Prices come from the product price list (retail or wholesale tier for that customer when applicable).</li>
        </ul>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/pos')) ?>">Open POS</a></p>
    </div>
</details>

<details class="help-acc" id="sales-history">
    <summary>Sales history</summary>
    <div class="help-acc-body">
        <p>Admin and manager. Open <strong>Sales history</strong> to browse receipts at your branch — retail <strong>POS-</strong> tickets and wholesale <strong>WS-</strong> sales. Filter by date or search receipt number, customer, or cashier.</p>
        <p>Managers can <strong>View</strong> and open the printable receipt. Use <strong>Return sale</strong> on a completed ticket to restore stock and reverse the sale amount (see Returns). Administrators can also <strong>Edit</strong> notes, mark a sale <strong>Cancelled</strong> (stock is restored), or <strong>Archive</strong> (moves the sale to Archives). Returned and cancelled sales are excluded from dashboard and report totals. New sales are run from <strong>Retail POS</strong>.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/sales')) ?>">Sales history</a></p>
    </div>
</details>

<details class="help-acc" id="archives">
    <summary>Archives</summary>
    <div class="help-acc-body">
        <p>Admin and manager can open <strong>Archives</strong> to see deleted sales, products, customers, suppliers, and purchase orders. Administrators can <strong>Restore</strong> a record to active lists or <strong>Delete forever</strong> to remove it from the database (blocked if other records still depend on it).</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/archives')) ?>">Archives</a></p>
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
        <p>Flow: <strong>quotation</strong> → <strong>convert</strong> → <strong>order</strong> + invoice + sale record. Any active customer at your branch can be quoted. Credit is checked on convert when that customer has a credit limit.</p>
        <h3>Quotations</h3>
        <ol>
            <li><strong>New quotation</strong>: pick a customer (all branch customers appear), or <strong>Add customer</strong> on the same form.</li>
            <li>Add lines with the product search table: <strong>In stock</strong>, quantity, and <strong>unit price</strong> (from the product catalogue). Save the quotation.</li>
            <li><strong>View</strong> or <strong>Edit</strong> open in a popup until the quote is converted. <strong>Convert</strong> asks for confirmation.</li>
            <li>Administrators can <strong>Archive</strong> a quotation that has not been converted.</li>
        </ol>
        <h3>Convert to order</h3>
        <ul>
            <li>Checks sellable stock and credit limit. If either fails, the quote stays open — no half order.</li>
            <li>On success: wholesale order number, invoice, and a <strong>WS-</strong> sale for reporting. Stock is deducted with FEFO (same as POS).</li>
        </ul>
        <h3>Orders</h3>
        <ul>
            <li><strong>View</strong> shows lines, batches, and totals.</li>
            <li><strong>Print</strong> opens a standalone order/invoice page and can send it straight to the printer. <strong>PDF</strong> downloads a file.</li>
            <li>Wholesale returns use <strong>Returns</strong> against the linked sale (same as retail receipts).</li>
        </ul>
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
        <p>Stock moves automatically on <strong>POS sales</strong>, <strong>wholesale convert</strong>, <strong>returns</strong>, and <strong>goods receipt</strong>. Manual adjustment is for corrections only.</p>
        <h3>Products</h3>
        <p>New product needs SKU, name, and retail price. <strong>Opening stock (add)</strong> creates a batch at your branch. Set wholesale price, barcode, and prescription/controlled flags as needed. The products list shows <strong>On hand</strong> (all batches at the branch).</p>
        <h3>Stock adjustment</h3>
        <p>On a product row, click <strong>Adjust</strong>. You see on-hand vs <strong>sellable</strong> (what POS and quotes can use). Choose <strong>Add stock</strong> or <strong>Remove stock</strong>, enter a quantity, and apply. Removals cannot exceed sellable quantity. Batches and branch totals stay matched.</p>
        <h3>Stock levels screen</h3>
        <p>Inventory shows on-hand quantity, reorder level, and status (OK / Low / Out). Per product you can <strong>Adjust</strong> stock (same as Products), set <strong>Reorder</strong>, or open <strong>Batches</strong> filtered to that item. <strong>Transfer stock</strong> moves FEFO quantity to another branch; use <strong>Transfers</strong> to review past moves.</p>
        <h3>Batches</h3>
        <p><strong>New batch</strong> manually receives stock at your branch (product, batch number, expiry, quantity, optional supplier). <strong>View</strong> shows full detail; <strong>Edit</strong> updates batch metadata and quantities (remaining cannot go below what was already sold). <strong>Recall</strong> blocks POS and quotes. <strong>Delete</strong> is only allowed when nothing was sold from the batch and remaining quantity is zero.</p>
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
            <li>Add the <strong>supplier</strong> (contact and payment terms). Supplier currency is for reference; PO totals are in <strong>GHS</strong>.</li>
            <li><strong>New PO</strong>: supplier, expected date, and line table (product, qty, unit cost). Cost prefills from the last purchase or batch; change it if the supplier price differs.</li>
            <li><strong>View</strong> a PO in a popup. <strong>Edit</strong> while nothing has been received yet. <strong>Receive</strong> when goods arrive.</li>
            <li>On receive, enter quantity per line, batch number, and expiry. Stock increases only after you submit the receipt.</li>
        </ol>
        <p>You can receive in parts until the ordered quantity is complete. Partial receipts block editing the PO lines (receive or archive first).</p>
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
        <p>Return a sale from <strong>Returns</strong> or Sales history: pick a <strong>completed</strong> receipt or wholesale <strong>WS-</strong> sale (search by number or customer), add a reason, and save. Stock goes back to the <strong>original batches</strong>, the sale is marked <strong>returned</strong>, dashboard and reports no longer count it, and the customer is refunded (cashbook out, credit reduced, or receivable reversed for invoiced wholesale).</p>
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
        <p>Only <strong>administrators</strong> can add, edit, or delete staff accounts. Everyone else can open the staff list to view licences only. The staff form covers name, email, phone, password (optional when editing), role, branch, licence, portal links, and active status. Email, phone, and portal links must be unique. If delete is blocked because of past sales, uncheck <strong>Account active</strong> instead. A <strong>Renew</strong> badge appears when a licence is due within 60 days.</p>
        <p class="help-jump"><a class="btn btn-sm" href="<?= e(url('/staff')) ?>">Staff</a></p>
    </div>
</details>

<details class="help-acc" id="accounting">
    <summary>Accounting and reports</summary>
    <div class="help-acc-body">
        <p><strong>Accounting</strong> shows cash in/out, receivables, a simple P&amp;L, and recent ledger lines. Retail POS, wholesale convert, goods receipt, returns, and credit collections post themselves — you do not type those journals.</p>
        <p><strong>Reports</strong>: pick from and to dates. Daily sales and top products include completed retail and wholesale sales at your branch (not returned tickets). Cashiers see this as <strong>My sales</strong>.</p>
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
        <p>Sell on POS (pick retail or wholesale price list first). Wholesale: quote, edit if needed, convert when stock and credit allow, print/PDF orders for customers, schedule deliveries. Receive POs the same day goods arrive. Collect account payments as cash comes in.</p>
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
                    <tr><td>Cannot remove more than sellable stock</td><td>Reduce the adjustment qty, or remove expired/recalled stock via batch recall — not sellable.</td></tr>
                    <tr><td>PO cannot be edited</td><td>Some quantity was already received. Finish receiving or archive the PO.</td></tr>
                    <tr><td>Blank print preview</td><td>Hard-refresh the page (Ctrl+F5) so the latest styles load, then print again.</td></tr>
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
