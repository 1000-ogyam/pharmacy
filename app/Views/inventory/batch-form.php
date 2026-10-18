<?php
/** @var \App\Models\Batch|null $batch */
$isModal = !empty($modal);
$isEdit = $batch !== null;
?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= $isEdit ? 'Edit batch' : 'New batch' ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form class="modal-form" method="post" action="<?= e($isEdit ? url('/inventory/batches/' . $batch->id) : url('/inventory/batches')) ?>">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>
    <?php if ($isModal): ?>
        <p class="modal-form-intro"><?= $isEdit ? 'Update batch details. Remaining quantity cannot go below what was already sold or transferred.' : 'Manually receive stock into a batch at your branch (same effect as goods receipt on a PO).' ?></p>
    <?php endif; ?>
    <div class="form-grid">
        <?php if (!$isEdit): ?>
        <div class="form-row full">
            <label>Product</label>
            <select name="product_id" required>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>" <?= (int) old('product_id', 0) === (int) $product['id'] ? 'selected' : '' ?>>
                        <?= e($product['name']) ?> (<?= e($product['sku']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="form-row"><label>Batch number</label><input name="batch_number" value="<?= e(old('batch_number', $batch?->batch_number ?? '')) ?>" required></div>
        <div class="form-row"><label>Expiry date</label><input type="date" name="expiry_date" value="<?= e(old('expiry_date', $batch?->expiry_date ?? '')) ?>" required></div>
        <div class="form-row"><label>Manufacture date</label><input type="date" name="manufacture_date" value="<?= e(old('manufacture_date', $batch?->manufacture_date ?? '')) ?>"></div>
        <?php if ($isEdit): ?>
        <div class="form-row"><label>Qty received</label><input type="number" min="0" step="0.01" name="quantity_received" value="<?= e(old('quantity_received', (string) $batch->quantity_received)) ?>" required></div>
        <div class="form-row"><label>Qty remaining</label><input type="number" min="0" step="0.01" name="quantity_remaining" value="<?= e(old('quantity_remaining', (string) $batch->quantity_remaining)) ?>" required></div>
        <?php else: ?>
        <div class="form-row"><label>Quantity</label><input type="number" min="0.01" step="0.01" name="quantity" value="<?= e(old('quantity', '')) ?>" required></div>
        <?php endif; ?>
        <div class="form-row"><label>Unit cost (GHS)</label><input type="number" min="0" step="0.0001" name="unit_cost" value="<?= e(old('unit_cost', $batch ? (string) $batch->unit_cost : '0')) ?>"></div>
        <div class="form-row full">
            <label>Supplier (optional)</label>
            <select name="supplier_id">
                <option value="">— None —</option>
                <?php $sid = (int) old('supplier_id', (int) ($batch?->supplier_id ?? 0)); ?>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= (int) $supplier['id'] ?>" <?= $sid === (int) $supplier['id'] ? 'selected' : '' ?>><?= e($supplier['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit"><?= $isEdit ? 'Save changes' : 'Create batch' ?></button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
