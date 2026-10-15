<div class="product-picker" data-product-picker>
    <input
        type="search"
        class="product-picker-search"
        data-product-picker-search
        placeholder="Search name or SKU…"
        autocomplete="off"
        aria-label="Search product"
    >
    <ul class="product-picker-list" data-product-picker-list hidden role="listbox"></ul>
    <select name="product_id[]" data-line-product class="product-picker-native" required>
        <option value="" data-stock="">— Product —</option>
        <?php foreach ($products as $product): ?>
            <?php
                $stock = $product->sellable ?? null;
                $sku = (string) ($product->sku ?? '');
                $search = strtolower(trim($product->name . ' ' . $sku . ' ' . (string) ($product->generic_name ?? '')));
                $label = $product->name . ($sku !== '' ? ' · ' . $sku : '');
            ?>
            <option
                value="<?= (int) $product->id ?>"
                data-search="<?= e($search) ?>"
                <?php if ($stock !== null): ?> data-stock="<?= e(format_qty($stock)) ?>"<?php endif; ?>
            ><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
</div>
