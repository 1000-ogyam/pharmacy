<div class="page-head">
    <div><h2>Returns</h2><p>Restores batch quantities through StockService.</p></div>
    <button type="button" class="btn" data-modal-src="#return-form" data-modal-title="Create return">Create return</button>
</div>
<template id="return-form">
    <form method="post" action="<?= e(url('/returns')) ?>">
        <?= csrf_field() ?>
        <div class="form-row"><label>Sale</label>
            <select name="sale_id" required>
                <option value="">Select sale</option>
                <?php foreach ($sales as $sale): ?>
                    <option value="<?= (int) $sale->id ?>"><?= e($sale->sale_number) ?> · <?= e(money($sale->total)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row" style="margin-top:8px;"><label>Reason</label><input name="reason" placeholder="Reason"></div>
        <button class="btn" style="margin-top:12px;">Save return</button>
    </form>
</template>
<div class="card">
<div class="table-wrap">
<table class="data">
    <thead><tr><th>No.</th><th>Customer</th><th>Reason</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['return_number']) ?></td>
            <td><?= e($row['customer_name'] ?: '—') ?></td>
            <td><?= e($row['reason']) ?></td>
            <td><?= e(money($row['total'])) ?></td>
            <td><?= e($row['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
