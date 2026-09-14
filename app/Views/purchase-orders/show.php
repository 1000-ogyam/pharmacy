<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= e($order->po_number) ?></h2>
        <p><?= e($supplier->name ?? '') ?> · <?= e($order->status) ?> · <?= e($order->currency_code) ?> @ <?= e($order->exchange_rate_at_purchase) ?></p>
    </div>
</div>
<div class="card"><div class="card-body">
<?php else: ?>
<p><?= e($supplier->name ?? '') ?> · <?= e($order->status) ?> · <?= e($order->currency_code) ?> @ <?= e($order->exchange_rate_at_purchase) ?></p>
<?php endif; ?>
<form method="post" action="<?= e(url('/purchase-orders/' . $order->id . '/receive')) ?>">
    <?= csrf_field() ?>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Receive now</th><th>Batch</th><th>Expiry</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e(format_qty($item['quantity_ordered'])) ?></td>
                    <td><?= e(format_qty($item['quantity_received'])) ?></td>
                    <td>
                        <input type="hidden" name="item_id[]" value="<?= (int) $item['id'] ?>">
                        <input type="number" step="0.01" name="qty_received[]" value="<?= e((string) max(0, (float)$item['quantity_ordered'] - (float)$item['quantity_received'])) ?>">
                    </td>
                    <td><input name="batch_number[]" placeholder="Batch no."></td>
                    <td><input type="date" name="expiry_date[]"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button class="btn" style="margin-top:14px;" type="submit">Verify goods receipt</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
