<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>Adjust stock</h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<p style="margin-bottom:12px;"><strong><?= e($product->name) ?></strong> (<?= e($product->sku) ?>)</p>
<p class="hint" style="margin-bottom:12px;">Set on-hand quantity at your branch. This updates stock levels and sellable batches (no purchase order required).</p>
<form method="post" action="<?= e(url('/products/' . $product->id . '/stock')) ?>">
    <?= csrf_field() ?>
    <div class="form-row">
        <label>Quantity on hand</label>
        <input name="quantity" type="number" min="0" step="0.01" value="<?= e(old('quantity', (string) $quantity)) ?>" required>
    </div>
    <button class="btn" style="margin-top:16px;" type="submit">Save stock</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
