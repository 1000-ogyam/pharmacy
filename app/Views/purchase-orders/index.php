<div class="page-head">
    <div><h2>Purchase orders</h2><p>Stock increases only after verified GRN.</p></div>
    <a class="btn" data-modal data-modal-title="New purchase order" data-modal-wide href="<?= e(url('/purchase-orders/create')) ?>">New PO</a>
</div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th>Currency</th><th>Total GHS</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= e($order['po_number']) ?></td>
            <td><?= e($order['supplier_name']) ?></td>
            <td><span class="badge badge-navy"><?= e($order['status']) ?></span></td>
            <td><?= e($order['currency_code']) ?> @ <?= e($order['exchange_rate_at_purchase']) ?></td>
            <td><?= e(money($order['total_ghs'])) ?></td>
            <td class="table-actions">
                <a class="btn btn-outline btn-sm" data-modal data-modal-title="<?= e($order['po_number']) ?>" data-modal-wide href="<?= e(url('/purchase-orders/' . $order['id'])) ?>">Receive</a>
                <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Archive this purchase order?" data-modal-title="Delete PO" data-action="<?= e(url('/purchase-orders/' . $order['id'])) ?>" data-method="DELETE">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
