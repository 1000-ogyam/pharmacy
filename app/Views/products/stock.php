<?php
/** @var float $onHand */
/** @var float $sellable */
$isModal = !empty($modal);
$modalTitle = 'Stock adjustment — ' . ($product->name ?? '');
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2>Stock adjustment</h2>
        <p><?= e($product->name) ?> · <?= e($product->sku) ?></p>
    </div>
    <a class="btn btn-outline" href="<?= e(url('/products')) ?>">Back to products</a>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form
    class="modal-form"
    method="post"
    action="<?= e(url('/products/' . $product->id . '/stock')) ?>"
    data-stock-adjust
    data-current-on-hand="<?= e((string) $onHand) ?>"
    data-sellable="<?= e((string) $sellable) ?>"
>
    <?= csrf_field() ?>
    <?php if (!empty($returnTo)): ?>
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>
    <?php if ($isModal): ?>
        <p class="modal-form-intro">Add or remove quantity at your branch. Batches and the product stock total stay matched (no purchase order required).</p>
    <?php else: ?>
        <p class="hint" style="margin-bottom:12px;">Add or remove quantity at your branch. Batches and the product stock total stay matched.</p>
    <?php endif; ?>

    <dl class="stock-adjust-summary">
        <div>
            <dt>On hand (all batches)</dt>
            <dd><strong><?= e(format_qty($onHand)) ?></strong></dd>
        </div>
        <div>
            <dt>Sellable (POS / quotes)</dt>
            <dd><strong><?= e(format_qty($sellable)) ?></strong></dd>
        </div>
    </dl>

    <fieldset class="form-row stock-adjust-mode">
        <legend>Adjustment type</legend>
        <label class="choice-inline">
            <input type="radio" name="adjustment_mode" value="add" <?= old('adjustment_mode', 'add') === 'remove' ? '' : 'checked' ?>>
            Add stock
        </label>
        <label class="choice-inline">
            <input type="radio" name="adjustment_mode" value="remove" <?= old('adjustment_mode') === 'remove' ? 'checked' : '' ?>>
            Remove stock
        </label>
    </fieldset>

    <div class="form-row">
        <label for="stock-adjust-amount">Quantity to adjust</label>
        <input
            id="stock-adjust-amount"
            name="amount"
            type="number"
            min="0.01"
            step="0.01"
            value="<?= e(old('amount', '')) ?>"
            placeholder="0"
            required
        >
        <p class="form-hint" data-adjust-hint>Adds to on-hand and sellable batches (adjustment batch).</p>
    </div>

    <p class="stock-adjust-preview muted" data-adjust-preview aria-live="polite"></p>

    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit" data-adjust-submit>Apply adjustment</button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
