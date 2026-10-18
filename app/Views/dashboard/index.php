<div class="dashboard-home">
    <div class="page-head">
        <div>
            <p class="eyebrow"><?= e(auth()->user()?->branch_name ?? 'Your branch') ?></p>
            <h2>Dashboard</h2>
            <p>Sales, stock, and expiry at a glance.</p>
        </div>
        <?php if ($canMutate): ?>
            <a class="btn" href="<?= e(url('/pos')) ?>">Open POS</a>
        <?php endif; ?>
    </div>

    <div class="dashboard-actions">
        <a class="btn btn-outline btn-sm" href="<?= e(url('/sales')) ?>">Sales</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/products')) ?>">Products</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/inventory')) ?>">Inventory</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/customers')) ?>">Customers</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/reports')) ?>">Reports</a>
    </div>

    <div class="grid grid-4">
        <a class="card stat" href="<?= e(url('/sales')) ?>">
            <div class="label">Today’s sales</div>
            <div class="value"><?= e(money($todaySales['total'] ?? 0)) ?></div>
            <div class="hint"><?= e((string) ($todaySales['cnt'] ?? 0)) ?> completed transactions</div>
        </a>
        <a class="card stat" href="<?= e(url('/reports')) ?>">
            <div class="label">This month</div>
            <div class="value"><?= e(money($monthSales['total'] ?? 0)) ?></div>
            <div class="hint">Completed sales total</div>
        </a>
        <a class="card stat" href="<?= e(url('/inventory')) ?>">
            <div class="label">Low stock</div>
            <div class="value"><?= e((string) ($lowStock['cnt'] ?? 0)) ?></div>
            <div class="hint">At or below reorder level</div>
        </a>
        <a class="card stat" href="<?= e(url('/inventory/batches')) ?>">
            <div class="label">Expiring (90 days)</div>
            <div class="value"><?= e((string) ($expiring['cnt'] ?? 0)) ?></div>
            <div class="hint">Batches with stock remaining</div>
        </a>
    </div>

    <div class="dashboard-panels grid grid-2">
        <div class="card">
            <div class="card-head">
                <span>Recent sales</span>
                <a class="card-head-link" href="<?= e(url('/sales')) ?>">View all</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Receipt</th><th>Customer</th><th>Total</th><th>When</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentSales as $sale): ?>
                        <tr>
                            <td><a href="<?= e(url('/sales/' . $sale['id'])) ?>"><?= e($sale['sale_number']) ?></a></td>
                            <td><?= e($sale['customer_name'] ?: 'Walk-in') ?></td>
                            <td><?= e(money($sale['total'])) ?></td>
                            <td><?= e(format_datetime($sale['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($recentSales === []): ?>
                        <tr><td colspan="4" class="empty">No sales yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
        </div>
        <div class="card">
            <div class="card-head">
                <span>Expiry alerts</span>
                <a class="card-head-link" href="<?= e(url('/inventory/batches')) ?>">Batches</a>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Qty</th></tr></thead>
                    <tbody>
                    <?php foreach ($alerts as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['batch_number']) ?></td>
                            <td>
                                <?php $days = days_until($row['expiry_date']); ?>
                                <span class="badge <?= $days !== null && $days <= 30 ? 'badge-danger' : 'badge-amber' ?>"><?= e(format_date($row['expiry_date'])) ?></span>
                            </td>
                            <td><?= e(format_qty($row['quantity_remaining'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($alerts === []): ?>
                        <tr><td colspan="4" class="empty">No urgent batch alerts.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php \App\Core\View::include('partials.pagination', ['page' => $alertPage, 'pages' => $alertPages, 'pageParam' => 'apage']); ?>
        </div>
    </div>
</div>
