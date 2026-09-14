<select name="product_id[]" data-line-product>
    <option value="" data-stock="">— Product —</option>
    <?php foreach ($products as $product): ?>
        <?php $stock = $product->sellable ?? null; ?>
        <option value="<?= (int) $product->id ?>"<?php if ($stock !== null): ?> data-stock="<?= e(format_qty($stock)) ?>"<?php endif; ?>><?= e($product->name) ?></option>
    <?php endforeach; ?>
</select>
