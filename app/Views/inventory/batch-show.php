<?php
/** @var \App\Models\Batch $batch */
$isModal = !empty($modal);
$days = days_until($batch->expiry_date);
$expired = $days !== null && $days < 0;
$recalled = (int) $batch->is_recalled === 1;
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2>Batch <?= e($batch->batch_number) ?></h2>
        <p><?= e($product->name) ?> · <?= e($product->sku) ?></p>
    </div>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<dl class="detail-grid">
    <div><dt>Product</dt><dd><?= e($product->name) ?></dd></div>
    <div><dt>Batch</dt><dd><?= e($batch->batch_number) ?></dd></div>
    <div><dt>Expiry</dt><dd><?= e(format_date($batch->expiry_date)) ?></dd></div>
    <div><dt>Manufacture</dt><dd><?= $batch->manufacture_date ? e(format_date($batch->manufacture_date)) : '—' ?></dd></div>
    <div><dt>Received</dt><dd><?= e(format_qty($batch->quantity_received)) ?></dd></div>
    <div><dt>Remaining</dt><dd><?= e(format_qty($batch->quantity_remaining)) ?></dd></div>
    <div><dt>Unit cost</dt><dd><?= e(money((float) $batch->unit_cost)) ?></dd></div>
    <div><dt>Supplier</dt><dd><?= e($supplierName ?? '—') ?></dd></div>
    <div><dt>Status</dt><dd>
        <?php if ($recalled): ?>
            <span class="badge badge-danger">Recalled</span>
            <?php if ($batch->recall_reason): ?><div class="muted" style="font-size:12px;margin-top:4px;"><?= e($batch->recall_reason) ?></div><?php endif; ?>
        <?php elseif ($expired): ?>
            <span class="badge badge-danger">Expired</span>
        <?php elseif ($days !== null && $days <= 60): ?>
            <span class="badge badge-amber">Expiring</span>
        <?php else: ?>
            <span class="badge badge-success">Sellable</span>
        <?php endif; ?>
    </dd></div>
    <div><dt>Used on sales / transfers</dt><dd><?= $usageCount > 0 ? 'Yes (' . (int) $usageCount . ' line(s))' : 'No' ?></dd></div>
</dl>
<?php if ($isModal): ?>
<div class="modal-form-actions table-actions-nowrap">
    <?php if ($canMutate): ?>
        <a class="btn btn-outline btn-sm" data-modal data-modal-title="Edit batch" href="<?= e(url('/inventory/batches/' . $batch->id . '/edit')) ?>">Edit</a>
        <?php if (!$recalled): ?>
        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Recall this batch and block sales?" data-modal-title="Recall batch" data-action="<?= e(url('/inventory/batches/' . $batch->id . '/recall')) ?>" data-recall-reason="Manual recall">Recall</button>
        <?php endif; ?>
        <?php if ($canDelete && $usageCount === 0 && (float) $batch->quantity_remaining <= 0.0001): ?>
        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Delete this batch record?" data-modal-title="Delete batch" data-action="<?= e(url('/inventory/batches/' . $batch->id)) ?>" data-method="DELETE">Delete</button>
        <?php endif; ?>
    <?php endif; ?>
    <button type="button" class="btn btn-outline btn-sm" data-modal-close>Close</button>
</div>
<?php else: ?>
    <p style="margin-top:16px;"><a class="btn btn-outline" href="<?= e(url('/inventory/batches')) ?>">Back to batches</a></p>
<?php endif; ?>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
