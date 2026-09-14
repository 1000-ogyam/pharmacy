<div class="page-head no-print">
    <div>
        <h2>Receipt <?= e($sale->sale_number) ?></h2>
        <p><?= e(format_datetime($sale->created_at)) ?></p>
    </div>
    <div>
        <button class="btn" type="button" onclick="window.print()">Print</button>
        <a class="btn btn-outline" href="<?= e(url('/pos')) ?>">New sale</a>
    </div>
</div>
<div class="card receipt-card">
    <div class="card-body">
        <h3>PL Pharmaceuticals</h3>
        <p><?= e($branch?->name ?: 'Retail branch') ?></p>
        <p>Receipt <?= e($sale->sale_number) ?> · <?= e(format_datetime($sale->created_at)) ?></p>
        <p>
            Customer: <?= e($customer?->name ?: 'Walk-in') ?>
            <?php if ($customer?->phone): ?> · <?= e($customer->phone) ?><?php endif; ?>
        </p>
        <p>Served by <?= e($cashier?->name ?: 'Staff') ?></p>
        <table class="data">
            <thead><tr><th>Item</th><th>Batch</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
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
        <div class="totals" style="max-width:280px;margin-left:auto;">
            <div><span>Subtotal</span><span><?= e(money($sale->subtotal)) ?></span></div>
            <div><span>Discount</span><span><?= e(money($sale->discount)) ?></span></div>
            <div class="grand"><span>Total</span><span><?= e(money($sale->total)) ?></span></div>
            <div><span>Payment</span><span><?= e($paymentLabel) ?></span></div>
        </div>
    </div>
</div>
