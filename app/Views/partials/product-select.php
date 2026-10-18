<?php
$linePrices = $linePrices ?? [];
$lineCosts = $lineCosts ?? [];
?>
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
                $unitPrice = $linePrices[(int) $product->id] ?? null;
                $unitCost = $lineCosts[(int) $product->id] ?? null;
            ?>
            <option
                value="<?= (int) $product->id ?>"
                data-search="<?= e($search) ?>"
                <?php if ($stock !== null): ?> data-stock="<?= e(format_qty($stock)) ?>"<?php endif; ?>
                <?php if ($unitPrice !== null): ?> data-unit-price="<?= e((string) $unitPrice) ?>"<?php endif; ?>
                <?php if ($unitCost !== null): ?> data-unit-cost="<?= e((string) $unitCost) ?>"<?php endif; ?>
            ><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
</div>
