<?php
/** @var \App\Models\WholesaleOrder $order */
/** @var \App\Models\Customer|null $customer */
/** @var list<array<string, mixed>> $items */
/** @var \App\Models\Quotation|null $quotation */
/** @var array<string, mixed>|null $invoice */
$isModal = !empty($modal);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= e($order->order_number) ?></h2>
        <p><?= e(format_datetime($order->created_at)) ?></p>
    </div>
    <div class="page-head-actions">
        <?php if ($invoice): ?>
            <a class="btn btn-outline" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order->id . '/invoice?print=1')) ?>">Print order</a>
            <a class="btn" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order->id . '/invoice/pdf')) ?>">PDF</a>
        <?php endif; ?>
        <a class="btn btn-outline" href="<?= e(url('/wholesale')) ?>">Back to wholesale</a>
    </div>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<div class="detail-meta detail-meta-compact">
    <p>Customer: <strong><?= e($customer?->name ?? '—') ?></strong>
        · Status: <strong><?= e(ucfirst((string) $order->status)) ?></strong>
        · Credit: <strong><?= e(money($order->credit_used)) ?></strong>
    </p>
    <?php if ($quotation): ?>
        <p>Quotation:
            <a href="<?= e(url('/wholesale/quotations/' . $quotation->id)) ?>" data-modal data-modal-wide data-modal-title="Quotation <?= e($quotation->quotation_number) ?>">
                <?= e($quotation->quotation_number) ?>
            </a>
        </p>
    <?php endif; ?>
    <?php if ($invoice): ?>
        <p>Invoice: <strong><?= e($invoice['invoice_number']) ?></strong>
            (<?= e(ucfirst((string) $invoice['status'])) ?>)
            <?php if (!empty($invoice['due_date'])): ?>
                · Due <?= e(format_date($invoice['due_date'])) ?>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <?php if ($order->notes): ?>
        <p>Notes: <?= e($order->notes) ?></p>
    <?php endif; ?>
</div>
<div class="table-wrap" style="margin-top:12px;">
    <table class="data line-table-compact">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch</th>
                <th class="col-qty">Quantity</th>
                <th class="col-price">Unit price</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?><?php if (!empty($item['sku'])): ?><div class="muted" style="font-size:12px;"><?= e($item['sku']) ?></div><?php endif; ?></td>
                <td><?= e($item['batch_number'] ?: '—') ?></td>
                <td class="col-qty"><?= e(format_qty($item['quantity'])) ?></td>
                <td class="col-price"><?= e(money($item['unit_price'])) ?></td>
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
    <div><span>Subtotal</span><span><?= e(money($order->subtotal)) ?></span></div>
    <?php if ((float) $order->discount > 0): ?>
        <div><span>Discount</span><span><?= e(money($order->discount)) ?></span></div>
    <?php endif; ?>
    <?php if ((float) $order->tax > 0): ?>
        <div><span>Tax</span><span><?= e(money($order->tax)) ?></span></div>
    <?php endif; ?>
    <div class="grand"><span>Total</span><span><?= e(money($order->total)) ?></span></div>
</div>
<?php if ($isModal): ?>
    <div class="modal-form-actions modal-form-actions-nowrap">
        <button type="button" class="btn btn-outline btn-sm" data-modal-close>Close</button>
        <?php if ($invoice): ?>
            <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order->id . '/invoice?print=1')) ?>">Print</a>
            <a class="btn btn-sm" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order->id . '/invoice/pdf')) ?>">PDF</a>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
