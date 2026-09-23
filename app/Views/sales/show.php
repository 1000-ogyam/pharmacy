<?php
/** @var \App\Models\Sale $sale */
/** @var bool $canManage */
?>
<div class="page-head">
    <div>
        <h2><?= e($sale->sale_number) ?></h2>
        <p><?= e(format_datetime($sale->created_at)) ?> · <?= e($branch?->name ?? 'Branch') ?></p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-outline" href="<?= e(url('/sales')) ?>">Back to list</a>
        <a class="btn btn-outline" href="<?= e(url('/pos/receipt/' . $sale->id)) ?>">Print receipt</a>
        <?php if ($canManage): ?>
            <a class="btn" data-modal data-modal-wide data-modal-title="Edit sale" href="<?= e(url('/sales/' . $sale->id . '/edit')) ?>">Edit</a>
        <?php endif; ?>
    </div>
</div>
<div class="card"><div class="card-body">
    <p>Cashier: <strong><?= e($cashier?->name ?? '—') ?></strong></p>
    <p>Customer: <strong><?= e($customer?->name ?? 'Walk-in') ?></strong></p>
    <p>Payment: <strong><?= e($paymentLabel) ?></strong> · Status: <strong><?= e(ucfirst((string) $sale->status)) ?></strong></p>
    <?php if ($sale->notes): ?><p>Notes: <?= e($sale->notes) ?></p><?php endif; ?>
    <div class="table-wrap" style="margin-top:16px;">
        <table class="data">
            <thead><tr><th>Product</th><th>Batch</th><th>Qty</th><th>Unit</th><th>Line total</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= e($item['product_name']) ?></td>
                    <td><?= e($item['batch_number']) ?></td>
                    <td><?= e(format_qty($item['quantity'])) ?></td>
                    <td><?= e(money($item['unit_price'])) ?></td>
                    <td><?= e(money($item['line_total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="totals" style="max-width:280px;margin-left:auto;margin-top:16px;">
        <div><span>Subtotal</span><span><?= e(money($sale->subtotal)) ?></span></div>
        <div><span>Discount</span><span><?= e(money($sale->discount)) ?></span></div>
        <div class="grand"><span>Total</span><span><?= e(money($sale->total)) ?></span></div>
    </div>
</div></div>
