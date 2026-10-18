<?php
/** @var \App\Models\PurchaseOrder $order */
/** @var \App\Models\Supplier|null $supplier */
/** @var list<array<string, mixed>> $items */
/** @var bool $canReceive */
$isModal = !empty($modal);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= e($order->po_number) ?></h2>
        <p><?= e(format_datetime($order->created_at)) ?></p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/purchase-orders')) ?>">Back to POs</a>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<div class="detail-meta">
    <p>Supplier: <strong><?= e($supplier?->name ?? '—') ?></strong></p>
    <p>Status: <strong><?= e(ucfirst((string) $order->status)) ?></strong>
        <?php if (!empty($order->expected_date)): ?>
            · Expected <strong><?= e(format_date($order->expected_date)) ?></strong>
        <?php endif; ?>
    </p>
</div>
<div class="table-wrap" style="margin-top:12px;">
    <table class="data line-table-compact">
        <thead>
            <tr>
                <th>Product</th>
                <th class="col-qty">Ordered</th>
                <th class="col-qty">Received</th>
                <th class="col-price">Unit cost</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?><?php if (!empty($item['sku'])): ?><div class="muted" style="font-size:12px;"><?= e($item['sku']) ?></div><?php endif; ?></td>
                <td class="col-qty"><?= e(format_qty($item['quantity_ordered'])) ?></td>
                <td class="col-qty"><?= e(format_qty($item['quantity_received'])) ?></td>
                <td class="col-price"><?= e(money($item['unit_cost'])) ?></td>
                <td class="col-amount"><?= e(money($item['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <tr><td colspan="5" class="empty">No line items.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="totals" style="max-width:280px;margin-left:auto;margin-top:16px;">
    <div class="grand"><span>Total</span><span><?= e(money($order->total_ghs)) ?></span></div>
</div>
<?php if ($isModal): ?>
    <div class="modal-form-actions modal-form-actions-nowrap">
        <button type="button" class="btn btn-outline btn-sm" data-modal-close>Close</button>
        <?php if ($canMutate && $canReceive): ?>
            <a class="btn btn-outline btn-sm" data-modal data-modal-wide data-modal-title="Receive goods" href="<?= e(url('/purchase-orders/' . $order->id . '/receive')) ?>">Receive</a>
        <?php endif; ?>
        <?php if ($canMutate && (string) $order->status !== 'received'): ?>
            <?php
                $receivedQty = 0.0;
                foreach ($items as $item) {
                    $receivedQty += (float) $item['quantity_received'];
                }
            ?>
            <?php if ($receivedQty <= 0.0001): ?>
            <a class="btn btn-sm" data-modal data-modal-wide data-modal-title="Edit purchase order" href="<?= e(url('/purchase-orders/' . $order->id . '/edit')) ?>">Edit</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
