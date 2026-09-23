<?php
/** @var \App\Models\Sale $sale */
$isModal = !empty($modal);
?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>Edit sale</h2><p><?= e($sale->sale_number) ?></p></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e(url('/sales/' . $sale->id)) ?>">
    <?= csrf_field() ?>
    <?= method_field('PUT') ?>
    <div class="form-grid">
        <div class="form-row"><label>Status</label>
            <select name="status" required>
                <option value="completed" <?= (string) old('status', $sale->status) === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= (string) old('status', $sale->status) === 'cancelled' ? 'selected' : '' ?>>Cancelled (restores stock)</option>
            </select>
        </div>
        <div class="form-row full"><label>Notes</label><textarea name="notes" rows="3" maxlength="255"><?= e(old('notes', $sale->notes ?? '')) ?></textarea></div>
        <div class="form-row full">
            <div class="help" style="color:var(--color-muted);">
                Total <?= e(money($sale->total)) ?> · Payment <?= e($paymentLabel) ?> · Cashier <?= e($cashier?->name ?? '—') ?>
            </div>
        </div>
    </div>
    <button class="btn" style="margin-top:16px;" type="submit">Save changes</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
