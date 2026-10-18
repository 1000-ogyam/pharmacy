<?php
/**
 * @var \App\Models\WholesaleOrder $order
 * @var array<string, mixed> $invoiceRow
 * @var \App\Models\Customer|null $customer
 * @var \App\Models\Branch|null $branch
 * @var list<array<string, mixed>> $items
 */
$invoiceNumber = (string) $invoiceRow['invoice_number'];
$subtotal = (float) ($invoiceRow['subtotal'] ?? $order->subtotal);
$tax = (float) ($invoiceRow['tax'] ?? $order->tax);
$total = (float) ($invoiceRow['total'] ?? $order->total);
$discount = (float) $order->discount;
?>
<div class="invoice-doc">
    <div class="invoice-doc-head">
        <div>
            <h3 style="margin:0 0 4px;font-size:16px;">PL Pharmaceuticals Ltd</h3>
            <p class="muted" style="margin:0;"><?= e($branch?->name ?: 'Wholesale') ?></p>
            <?php if (!empty($branch?->address)): ?>
                <p class="muted" style="margin:4px 0 0;font-size:12px;"><?= e($branch->address) ?></p>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <p style="margin:0;font-size:17px;font-weight:700;">WHOLESALE ORDER</p>
            <p style="margin:4px 0 0;font-size:15px;font-weight:600;"><?= e($order->order_number) ?></p>
            <p class="muted" style="margin:4px 0 0;font-size:12px;">Invoice <?= e($invoiceNumber) ?></p>
            <p class="muted" style="margin:2px 0 0;font-size:12px;">Date: <?= e(format_date($invoiceRow['created_at'] ?? $order->created_at)) ?></p>
            <?php if (!empty($invoiceRow['due_date'])): ?>
                <p class="muted" style="margin:2px 0 0;font-size:12px;">Due: <?= e(format_date($invoiceRow['due_date'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="invoice-doc-grid">
        <div>
            <p class="muted" style="margin:0 0 4px;font-size:10px;text-transform:uppercase;font-weight:600;">Bill to</p>
            <p style="margin:0;font-weight:600;"><?= e($customer?->name ?? '—') ?></p>
            <?php if (!empty($customer?->phone)): ?>
                <p class="muted" style="margin:4px 0 0;"><?= e($customer->phone) ?></p>
            <?php endif; ?>
            <?php if (!empty($customer?->email)): ?>
                <p class="muted" style="margin:2px 0 0;"><?= e($customer->email) ?></p>
            <?php endif; ?>
            <?php if (!empty($customer?->address)): ?>
                <p class="muted" style="margin:4px 0 0;"><?= e($customer->address) ?></p>
            <?php endif; ?>
        </div>
        <div style="text-align:right;">
            <p class="muted" style="margin:0 0 4px;font-size:10px;text-transform:uppercase;font-weight:600;">Status</p>
            <p style="margin:0;"><?= e(ucfirst((string) ($invoiceRow['status'] ?? 'issued'))) ?></p>
            <?php if ((float) $order->credit_used > 0): ?>
                <p class="muted" style="margin:6px 0 0;">Credit: <?= e(money($order->credit_used)) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th>Qty</th>
                <th>Unit price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?><?php if (!empty($item['sku'])): ?> <span class="muted">(<?= e($item['sku']) ?>)</span><?php endif; ?></td>
                <td><?= e($item['batch_number'] ?: '—') ?></td>
                <td><?= e(format_qty($item['quantity'])) ?></td>
                <td><?= e(money($item['unit_price'])) ?></td>
                <td><?= e(money($item['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div><span>Subtotal</span><span><?= e(money($subtotal)) ?></span></div>
        <?php if ($discount > 0): ?>
            <div><span>Discount</span><span><?= e(money($discount)) ?></span></div>
        <?php endif; ?>
        <?php if ($tax > 0): ?>
            <div><span>Tax</span><span><?= e(money($tax)) ?></span></div>
        <?php endif; ?>
        <div class="grand"><span>Total (GHS)</span><span><?= e(money($total)) ?></span></div>
    </div>

    <p class="muted" style="margin-top:20px;font-size:11px;">Thank you for your business. Quote invoice <?= e($invoiceNumber) ?> on payment.</p>
</div>
