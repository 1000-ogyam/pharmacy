<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>Reorder level</h2><p><?= e($product->name) ?></p></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form class="modal-form" method="post" action="<?= e(url('/inventory/products/' . $product->id . '/reorder')) ?>">
    <?= csrf_field() ?>
    <?= method_field('PUT') ?>
    <?php if ($isModal): ?>
        <p class="modal-form-intro">Low-stock alerts use this threshold for <?= e($product->name) ?> at your branch.</p>
    <?php endif; ?>
    <div class="form-row">
        <label for="reorder-level">Reorder level</label>
        <input id="reorder-level" name="reorder_level" type="number" min="0" step="0.01" value="<?= e(old('reorder_level', (string) $reorderLevel)) ?>" required>
    </div>
    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit">Save</button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
