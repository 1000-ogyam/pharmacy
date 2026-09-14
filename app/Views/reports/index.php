<div class="page-head"><div><h2>Reports</h2></div></div>
<div class="card"><div class="card-body">
<form class="searchbar" method="get">
    <input type="date" name="from" value="<?= e($from) ?>">
    <input type="date" name="to" value="<?= e($to) ?>">
    <button class="btn">Filter</button>
</form>
</div></div>
<div class="grid grid-2" style="margin-top:16px;">
    <div class="card">
        <div class="card-head">Daily sales</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Txns</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr><td><?= e(format_date($row['d'])) ?></td><td><?= e((string) $row['cnt']) ?></td><td><?= e(money($row['total'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
    </div>
    <div class="card">
        <div class="card-head">Top products</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Product</th><th>Qty</th><th>Total</th></tr></thead>
                <tbody>
                <?php foreach ($top as $row): ?>
                    <tr><td><?= e($row['name']) ?></td><td><?= e(format_qty($row['qty'])) ?></td><td><?= e(money($row['total'])) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $topPage, 'pages' => $topPages, 'pageParam' => 'tpage']); ?>
    </div>
</div>
