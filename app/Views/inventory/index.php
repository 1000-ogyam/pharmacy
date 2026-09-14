<div class="page-head">
    <div>
        <h2>Stock levels</h2>
        <p>Branch-scoped quantities. Stock only increases after a verified goods receipt.</p>
    </div>
    <a class="btn" data-modal data-modal-title="Transfer stock" href="<?= e(url('/inventory/transfers/create')) ?>">Transfer stock</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>SKU</th><th>Product</th><th>On hand</th><th>Reorder</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $qty = (float) ($row['quantity'] ?? 0); $reorder = (float) ($row['reorder_level'] ?? 10); ?>
                <tr>
                    <td><?= e($row['sku']) ?></td>
                    <td><?= e($row['name']) ?></td>
                    <td><?= e(format_qty($qty)) ?></td>
                    <td><?= e(format_qty($reorder)) ?></td>
                    <td>
                        <?php if ($qty <= 0): ?>
                            <span class="badge badge-danger">Out</span>
                        <?php elseif ($qty <= $reorder): ?>
                            <span class="badge badge-amber">Low</span>
                        <?php else: ?>
                            <span class="badge badge-success">OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
