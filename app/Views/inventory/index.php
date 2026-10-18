<div class="page-head">
    <div>
        <h2>Stock levels</h2>
        <p>Branch on-hand totals, reorder alerts, and quick adjustments.</p>
    </div>
    <div class="page-head-actions table-actions-nowrap">
        <a class="btn btn-outline btn-sm" href="<?= e(url('/inventory/batches')) ?>">Batches</a>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/inventory/transfers')) ?>">Transfers</a>
        <?php if ($canMutate): ?>
        <a class="btn btn-sm" data-modal data-modal-title="Transfer stock" href="<?= e(url('/inventory/transfers/create')) ?>">Transfer stock</a>
        <?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data data-compact">
            <thead><tr><th>SKU</th><th>Product</th><th>On hand</th><th>Reorder</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <?php
                    $qty = (float) ($row['quantity'] ?? 0);
                    $reorder = (float) ($row['reorder_level'] ?? 10);
                    $pid = (int) $row['id'];
                ?>
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
                    <td class="table-actions table-actions-nowrap">
                        <a class="btn btn-outline btn-xs" href="<?= e(url('/inventory/batches?product_id=' . $pid)) ?>">Batches</a>
                        <?php if ($canMutate): ?>
                        <a class="btn btn-outline btn-xs" data-modal data-modal-title="Stock adjustment — <?= e($row['name']) ?>" href="<?= e(url('/products/' . $pid . '/stock?return_to=' . rawurlencode('/inventory'))) ?>">Adjust</a>
                        <a class="btn btn-outline btn-xs" data-modal data-modal-title="Reorder — <?= e($row['name']) ?>" href="<?= e(url('/inventory/products/' . $pid . '/reorder')) ?>">Reorder</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
