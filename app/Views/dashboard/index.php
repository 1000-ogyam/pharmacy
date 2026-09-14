<div class="page-head">
    <div>
        <p class="eyebrow"><?= e(auth()->user()?->branch_name ?? 'Your branch') ?></p>
        <h2>Good <?= (int) date('H') < 12 ? 'morning' : ((int) date('H') < 17 ? 'afternoon' : 'evening') ?></h2>
        <p>A quiet read of today’s counter, stock, and what is close to expiry.</p>
    </div>
    <a class="btn btn-lg" href="<?= e(url('/pos')) ?>">Open POS</a>
</div>

<div class="grid grid-4">
    <div class="card stat">
        <div class="label">Today's sales</div>
        <div class="value"><?= e(money($todaySales['total'] ?? 0)) ?></div>
        <div class="hint"><?= e((string) ($todaySales['cnt'] ?? 0)) ?> transactions</div>
    </div>
    <div class="card stat">
        <div class="label">This month</div>
        <div class="value"><?= e(money($monthSales['total'] ?? 0)) ?></div>
        <div class="hint">Gross sales</div>
    </div>
    <div class="card stat">
        <div class="label">Low stock</div>
        <div class="value"><?= e((string) ($lowStock['cnt'] ?? 0)) ?></div>
        <div class="hint">At or below reorder level</div>
    </div>
    <div class="card stat">
        <div class="label">Expiring (90 days)</div>
        <div class="value"><?= e((string) ($expiring['cnt'] ?? 0)) ?></div>
        <div class="hint">FEFO will prefer these first</div>
    </div>
</div>

<div class="grid grid-2" style="margin-top:16px;">
    <div class="card">
        <div class="card-head">Recent sales</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>No.</th><th>Customer</th><th>Total</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach ($recentSales as $sale): ?>
                    <tr>
                        <td><?= e($sale['sale_number']) ?></td>
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
        <div class="card-head">Expiry & recall alerts</div>
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
