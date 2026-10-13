<?php
/** @var list<\App\Models\Sale> $sales */
/** @var int $preselectSaleId */
?>
<div class="page-head">
    <div><h2>Returns</h2><p>Return a previous sale — stock is restored and sales totals are reversed.</p></div>
    <button type="button" class="btn" data-modal-src="#return-form" data-modal-title="Return a sale">Return a sale</button>
</div>
<div class="card"><div class="card-body">
    <form class="searchbar" method="get" action="<?= e(url('/returns')) ?>">
        <input type="search" name="sq" value="<?= e($sq) ?>" placeholder="Search returnable sales (receipt or customer)…" autocomplete="off">
        <?php if ($preselectSaleId > 0): ?>
            <input type="hidden" name="sale_id" value="<?= (int) $preselectSaleId ?>">
        <?php endif; ?>
    </form>
</div></div>
<template id="return-form">
    <form method="post" action="<?= e(url('/returns')) ?>">
        <?= csrf_field() ?>
        <p class="hint" style="margin-bottom:12px;">Choose a completed sale with no existing return. The full receipt amount is refunded (cash/mobile/card out or credit reduced).</p>
        <div class="form-row"><label>Sale</label>
            <select name="sale_id" required>
                <option value="">Select sale</option>
                <?php foreach ($sales as $sale): ?>
                    <?php
                        $selected = $preselectSaleId === (int) $sale->id ? ' selected' : '';
                        $label = $sale->sale_number . ' · ' . money($sale->total) . ' · ' . format_datetime($sale->created_at);
                    ?>
                    <option value="<?= (int) $sale->id ?>"<?= $selected ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($sales === []): ?>
            <p class="hint">No returnable sales found. Use search on the Returns page or pick a completed sale from Sales history.</p>
        <?php endif; ?>
        <div class="form-row" style="margin-top:8px;"><label>Reason</label><input name="reason" placeholder="Reason"></div>
        <button class="btn" style="margin-top:12px;" type="submit"<?= $sales === [] ? ' disabled' : '' ?>>Process return</button>
    </form>
</template>
<div class="card" style="margin-top:16px;">
<div class="table-wrap">
<table class="data">
    <thead><tr><th>No.</th><th>Sale</th><th>Customer</th><th>Reason</th><th>Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['return_number']) ?></td>
            <td><?php if (!empty($row['sale_id'])): ?><a href="<?= e(url('/sales/' . $row['sale_id'])) ?>"><?= e($row['sale_number'] ?? '—') ?></a><?php else: ?>—<?php endif; ?></td>
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
