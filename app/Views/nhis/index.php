<div class="page-head">
    <div><h2>NHIS claims</h2></div>
    <button type="button" class="btn" data-modal-src="#nhis-form" data-modal-title="Submit NHIS claim">Submit claim</button>
</div>
<template id="nhis-form">
    <form method="post" action="<?= e(url('/nhis')) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <label>Prescription</label>
            <select name="prescription_id" required>
                <?php foreach ($prescriptions as $rx): ?>
                    <option value="<?= (int) $rx->id ?>"><?= e($rx->prescription_number) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row" style="margin-top:8px;"><label>Amount claimed</label><input type="number" step="0.01" name="amount" required></div>
        <button class="btn" style="margin-top:12px;">Submit claim</button>
    </form>
</template>
<div class="card"><div class="table-wrap">
    <table class="data">
        <thead><tr><th>Claim</th><th>Patient</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($claims as $claim): ?>
            <tr>
                <td><?= e($claim['claim_number']) ?></td>
                <td><?= e($claim['customer_name']) ?></td>
                <td><?= e(money($claim['amount_claimed'])) ?></td>
                <td><?= e($claim['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
