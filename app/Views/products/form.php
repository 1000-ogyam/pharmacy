<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div><h2><?= e($product ? 'Edit product' : 'New product') ?></h2></div>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e($product ? url('/products/' . $product->id) : url('/products')) ?>">
    <?= csrf_field() ?>
    <?php if ($product): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="form-grid">
        <div class="form-row"><label>SKU</label><input name="sku" value="<?= e(old('sku', $product?->sku ?? '')) ?>" required></div>
        <div class="form-row"><label>Barcode</label><input name="barcode" value="<?= e(old('barcode', $product?->barcode ?? '')) ?>"></div>
        <div class="form-row full"><label>Name</label><input name="name" value="<?= e(old('name', $product?->name ?? '')) ?>" required></div>
        <?php if ($product): ?>
        <div class="form-row"><label>Generic name</label><input name="generic_name" value="<?= e(old('generic_name', $product->generic_name ?? '')) ?>"></div>
        <?php else: ?>
        <div class="form-row"><label>Quantity</label><input name="quantity" type="number" min="0" step="1" value="<?= e(old('quantity', '0')) ?>" placeholder="Opening stock at your branch"></div>
        <?php endif; ?>
        <div class="form-row"><label>Category</label><input name="category" value="<?= e(old('category', $product?->category ?? '')) ?>"></div>
        <div class="form-row"><label>Dosage form</label><input name="dosage_form" value="<?= e(old('dosage_form', $product?->dosage_form ?? '')) ?>"></div>
        <div class="form-row"><label>Strength</label><input name="strength" value="<?= e(old('strength', $product?->strength ?? '')) ?>"></div>
        <div class="form-row"><label>Manufacturer</label><input name="manufacturer" value="<?= e(old('manufacturer', $product?->manufacturer ?? '')) ?>"></div>
        <?php if (!$product): ?>
        <div class="form-row"><label>Retail price (GHS)</label><input name="retail_price" type="number" step="0.01" required></div>
        <div class="form-row"><label>Wholesale price</label><input name="wholesale_price" type="number" step="0.01"></div>
        <div class="form-row"><label>Base unit</label><input name="unit_name" value="unit"></div>
        <?php endif; ?>
        <div class="form-row"><label><input type="checkbox" name="requires_prescription" value="1" <?= !empty($product?->requires_prescription) ? 'checked' : '' ?>> Requires prescription</label></div>
        <div class="form-row"><label><input type="checkbox" name="is_controlled" value="1" <?= !empty($product?->is_controlled) ? 'checked' : '' ?>> Controlled</label></div>
        <div class="form-row full"><label>Description</label><textarea name="description" rows="3"><?= e(old('description', $product?->description ?? '')) ?></textarea></div>
    </div>
    <button class="btn" style="margin-top:16px;" type="submit">Save product</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
