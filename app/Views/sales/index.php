<?php /** @var bool $canManage */ ?>
<div class="page-head">
    <div>
        <h2>Sales history</h2>
        <p>Retail and counter sales at your branch.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-outline" href="<?= e(url('/archives?type=sales')) ?>">Archives</a>
        <?php if ($canManage): ?>
            <a class="btn" href="<?= e(url('/pos')) ?>">New sale (POS)</a>
        <?php endif; ?>
    </div>
</div>
<div class="card"><div class="card-body">
    <form class="searchbar" method="get" action="<?= e(url('/sales')) ?>" data-live-search data-live-search-target="[data-live-results]">
        <input type="date" name="from" value="<?= e($from) ?>" aria-label="From date">
        <input type="date" name="to" value="<?= e($to) ?>" aria-label="To date">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Receipt, customer, cashier…" autocomplete="off">
    </form>
</div></div>
<div class="card" style="margin-top:16px;" data-live-results><div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Date</th>
                <th>Cashier</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><a href="<?= e(url('/sales/' . $row['id'])) ?>"><?= e($row['sale_number']) ?></a></td>
                <td><?= e(format_datetime($row['created_at'])) ?></td>
                <td><?= e($row['cashier_name']) ?></td>
                <td><?= e($row['customer_name'] ?: 'Walk-in') ?></td>
                <td><?= e(money($row['total'])) ?></td>
                <td><?= e(ucfirst((string) $row['payment_status'])) ?></td>
                <td>
                    <?php if ((string) ($row['status'] ?? '') === 'cancelled'): ?>
                        <span class="badge badge-danger">Cancelled</span>
                    <?php else: ?>
                        <span class="badge badge-success"><?= e(ucfirst((string) $row['status'])) ?></span>
                    <?php endif; ?>
                </td>
                <td class="table-actions">
                    <a class="btn btn-outline btn-sm" href="<?= e(url('/sales/' . $row['id'])) ?>">View</a>
                    <a class="btn btn-outline btn-sm" href="<?= e(url('/pos/receipt/' . $row['id'])) ?>">Receipt</a>
                    <?php if ($canManage): ?>
                        <a class="btn btn-outline btn-sm" data-modal data-modal-wide data-modal-title="Edit sale" href="<?= e(url('/sales/' . $row['id'] . '/edit')) ?>">Edit</a>
                        <?php if ((string) ($row['status'] ?? '') !== 'cancelled'): ?>
                        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Archive this sale? Stock will be restored if not already cancelled." data-modal-title="Archive sale" data-action="<?= e(url('/sales/' . $row['id'])) ?>" data-method="DELETE">Archive</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($rows === []): ?>
            <tr><td colspan="8">No sales found for this filter.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/sales']); ?>
</div>
