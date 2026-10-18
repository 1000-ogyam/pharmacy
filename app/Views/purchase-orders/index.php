<div class="page-head">
    <div><h2>Purchase orders</h2><p>Stock increases only after verified goods receipt.</p></div>
    <?php if ($canMutate): ?>
    <a class="btn" data-modal data-modal-title="New purchase order" data-modal-wide href="<?= e(url('/purchase-orders/create')) ?>">New PO</a>
    <?php endif; ?>
</div>
<div class="card"><div class="table-wrap">
<table class="data data-compact">
    <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= e($order['po_number']) ?></td>
            <td><?= e($order['supplier_name']) ?></td>
            <td><span class="badge badge-navy"><?= e($order['status']) ?></span></td>
            <td><?= e(money($order['total_ghs'])) ?></td>
            <td class="table-actions table-actions-nowrap">
                <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="<?= e($order['po_number']) ?>" href="<?= e(url('/purchase-orders/' . $order['id'])) ?>">View</a>
                <?php
                    $receivedTotal = (float) ($order['received_total'] ?? 0);
                    $poEditable = $canMutate && (string) $order['status'] !== 'received' && $receivedTotal <= 0.0001;
                    $poReceivable = $canMutate && (string) $order['status'] !== 'received';
                ?>
                <?php if ($poEditable): ?>
                    <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="Edit purchase order" href="<?= e(url('/purchase-orders/' . $order['id'] . '/edit')) ?>">Edit</a>
                <?php endif; ?>
                <?php if ($poReceivable): ?>
                    <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="Receive goods" href="<?= e(url('/purchase-orders/' . $order['id'] . '/receive')) ?>">Receive</a>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                <button type="button" class="btn btn-danger btn-xs" data-modal-confirm="Archive this purchase order?" data-modal-title="Archive PO" data-confirm-danger data-action="<?= e(url('/purchase-orders/' . $order['id'])) ?>" data-method="DELETE">Archive</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($orders === []): ?>
        <tr><td colspan="5" class="empty">No purchase orders yet.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
