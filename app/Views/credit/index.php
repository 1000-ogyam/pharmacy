<div class="page-head"><div><h2>Credit accounts</h2><p>AR balances and collections.</p></div></div>
<template id="collect-form">
    <form method="post" action="">
        <?= csrf_field() ?>
        <div class="form-row"><label>Amount</label><input type="number" step="0.01" name="amount" required></div>
        <div class="form-row" style="margin-top:8px;"><label>Method</label>
            <select name="method"><option value="cash">Cash</option><option value="mobile_money">MoMo</option><option value="bank">Bank</option></select>
        </div>
        <button class="btn" style="margin-top:12px;">Apply payment</button>
    </form>
</template>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>Customer</th><th>Limit</th><th>Balance</th><th>Available</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= e($customer['name']) ?></td>
            <td><?= e(money($customer['credit_limit'])) ?></td>
            <td><?= e(money($customer['credit_balance'])) ?></td>
            <td><?= e(money((float)$customer['credit_limit'] - (float)$customer['credit_balance'])) ?></td>
            <td>
                <button type="button" class="btn btn-sm" data-modal-src="#collect-form" data-modal-title="Collect from <?= e($customer['name']) ?>" data-set-action="<?= e(url('/credit/' . $customer['id'] . '/collect')) ?>">Collect</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
