<?php
/** @var \App\Models\Quotation $quote */
/** @var \App\Models\Customer|null $customer */
/** @var list<array<string, mixed>> $items */
/** @var array{id:int,order_number:string}|null $linkedOrder */
$isModal = !empty($modal);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= e($quote->quotation_number) ?></h2>
        <p><?= e(format_datetime($quote->created_at)) ?></p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/wholesale')) ?>">Back to wholesale</a>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<div class="detail-meta detail-meta-compact">
    <p>Customer: <strong><?= e($customer?->name ?? '—') ?></strong></p>
    <p>Status: <strong><?= e(ucfirst((string) $quote->status)) ?></strong>
        <?php if (!empty($quote->valid_until)): ?>
            · Valid until <strong><?= e(format_date($quote->valid_until)) ?></strong>
        <?php endif; ?>
    </p>
    <?php if ($linkedOrder): ?>
        <p>Order:
            <a href="<?= e(url('/wholesale/orders/' . $linkedOrder['id'])) ?>" data-modal data-modal-wide data-modal-title="Order <?= e($linkedOrder['order_number']) ?>">
                <?= e($linkedOrder['order_number']) ?>
            </a>
        </p>
    <?php endif; ?>
</div>
<div class="table-wrap" style="margin-top:12px;">
    <table class="data line-table-compact">
        <thead>
            <tr>
                <th>Product</th>
                <th class="col-qty">Quantity</th>
                <th class="col-price">Unit price</th>
                <th class="col-amount">Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?><?php if (!empty($item['sku'])): ?><div class="muted" style="font-size:12px;"><?= e($item['sku']) ?></div><?php endif; ?></td>
                <td class="col-qty"><?= e(format_qty($item['quantity'])) ?></td>
                <td class="col-price"><?= e(money($item['unit_price'])) ?></td>
                <td class="col-amount"><?= e(money($item['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($items === []): ?>
            <tr><td colspan="4" class="empty">No line items.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<div class="totals" style="max-width:280px;margin-left:auto;margin-top:16px;">
    <div><span>Subtotal</span><span><?= e(money($quote->subtotal)) ?></span></div>
    <?php if ((float) $quote->discount > 0): ?>
        <div><span>Discount</span><span><?= e(money($quote->discount)) ?></span></div>
    <?php endif; ?>
    <?php if ((float) $quote->tax > 0): ?>
        <div><span>Tax</span><span><?= e(money($quote->tax)) ?></span></div>
    <?php endif; ?>
    <div class="grand"><span>Total</span><span><?= e(money($quote->total)) ?></span></div>
</div>
<?php if ($isModal): ?>
    <div class="modal-form-actions modal-form-actions-nowrap">
        <button type="button" class="btn btn-outline btn-sm" data-modal-close>Close</button>
        <?php if ($canMutate && (string) $quote->status !== 'converted'): ?>
            <a class="btn btn-outline btn-sm" data-modal data-modal-wide data-modal-title="Edit quotation" href="<?= e(url('/wholesale/quotations/' . $quote->id . '/edit')) ?>">Edit</a>
            <button type="button" class="btn btn-sm" data-modal-confirm="Convert this quotation to an order and invoice?" data-modal-title="Convert quotation" data-method="POST" data-action="<?= e(url('/wholesale/quotations/' . $quote->id . '/convert')) ?>">Convert</button>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
