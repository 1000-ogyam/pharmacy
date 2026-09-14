<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= e($rx->prescription_number) ?></h2>
        <p>Status: <?= e($rx->status) ?> · Prescribed by <?= e($rx->prescribed_by) ?></p>
    </div>
</div>
<div class="card">
<?php else: ?>
<p>Status: <?= e($rx->status) ?> · Prescribed by <?= e($rx->prescribed_by) ?></p>
<?php endif; ?>
<div class="table-wrap">
    <table class="data">
        <thead><tr><th>Product</th><th>Dosage</th><th>Qty</th><th>Dispensed</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
                <td><?= e($item['dosage']) ?></td>
                <td><?= e(format_qty($item['quantity'])) ?></td>
                <td><?= e(format_qty($item['quantity_dispensed'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($rx->status !== 'dispensed'): ?>
<div class="<?= $isModal ? '' : 'card-body' ?>" style="margin-top:12px;">
    <form method="post" action="<?= e(url('/prescriptions/' . $rx->id . '/dispense')) ?>">
        <?= csrf_field() ?>
        <div class="form-row" style="max-width:240px;">
            <label>Payment</label>
            <select name="payment_method">
                <option value="cash">Cash</option>
                <option value="nhis">NHIS</option>
                <option value="credit">Credit</option>
            </select>
        </div>
        <button class="btn" style="margin-top:10px;">Dispense via POS / FEFO</button>
    </form>
</div>
<?php endif; ?>
<?php if (!$isModal): ?>
</div>
<?php endif; ?>
