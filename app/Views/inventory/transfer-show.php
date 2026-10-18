<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= e($transfer->transfer_number) ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<dl class="detail-grid" style="margin-bottom:16px;">
    <div><dt>From</dt><dd><?= e($fromName) ?></dd></div>
    <div><dt>To</dt><dd><?= e($toName) ?></dd></div>
    <div><dt>Status</dt><dd><?= e($transfer->status) ?></dd></div>
    <div><dt>Created</dt><dd><?= e(format_datetime($transfer->created_at)) ?></dd></div>
</dl>
<div class="table-wrap">
    <table class="data data-compact">
        <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Qty</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $line): ?>
            <tr>
                <td><?= e($line['product_name']) ?><div class="muted" style="font-size:12px;"><?= e($line['sku']) ?></div></td>
                <td><?= e($line['batch_number']) ?></td>
                <td><?= e(format_date($line['expiry_date'])) ?></td>
                <td><?= e(format_qty($line['quantity'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($isModal): ?>
<div class="modal-form-actions">
    <button type="button" class="btn btn-outline" data-modal-close>Close</button>
</div>
<?php else: ?>
    <p style="margin-top:16px;"><a class="btn btn-outline" href="<?= e(url('/inventory/transfers')) ?>">Back</a></p>
<?php endif; ?>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
