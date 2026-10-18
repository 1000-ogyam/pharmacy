<?php
/** @var \App\Models\PurchaseOrder $order */
/** @var \App\Models\Supplier|null $supplier */
/** @var list<array<string, mixed>> $items */
$isModal = !empty($modal);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2>Receive <?= e($order->po_number) ?></h2>
        <p><?= e($supplier->name ?? '') ?></p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/purchase-orders/' . $order->id)) ?>">View PO</a>
</div>
<div class="card"><div class="card-body">
<?php else: ?>
<p class="modal-form-intro"><?= e($supplier->name ?? '') ?> · Enter batch and expiry for each line you receive now.</p>
<?php endif; ?>
<form class="modal-form" method="post" action="<?= e(url('/purchase-orders/' . $order->id . '/receive')) ?>">
    <?= csrf_field() ?>
    <div class="table-wrap">
        <table class="data line-table-compact">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="col-qty">Ordered</th>
                    <th class="col-qty">Received</th>
                    <th class="col-qty">Receive now</th>
                    <th>Batch</th>
                    <th>Expiry</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <?php
                    $left = max(0, (float) $item['quantity_ordered'] - (float) $item['quantity_received']);
                ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td class="col-qty"><?= e(format_qty($item['quantity_ordered'])) ?></td>
                    <td class="col-qty"><?= e(format_qty($item['quantity_received'])) ?></td>
                    <td class="col-qty">
                        <input type="hidden" name="item_id[]" value="<?= (int) $item['id'] ?>">
                        <input type="number" step="0.01" min="0" name="qty_received[]" value="<?= $left > 0 ? e((string) $left) : '0' ?>" <?= $left <= 0 ? 'disabled' : '' ?>>
                    </td>
                    <td><input name="batch_number[]" placeholder="Batch no." <?= $left <= 0 ? 'disabled' : '' ?>></td>
                    <td><input type="date" name="expiry_date[]" <?= $left <= 0 ? 'disabled' : '' ?>></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit">Verify goods receipt</button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
