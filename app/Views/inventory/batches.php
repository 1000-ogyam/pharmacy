<div class="page-head">
    <div>
        <h2>Batches</h2>
        <p>Expired and recalled batches cannot be sold — blocked in FefoService / Batch::isSellable().</p>
    </div>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Remaining</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($batches as $batch): ?>
                <?php
                    $days = days_until($batch['expiry_date']);
                    $expired = $days !== null && $days < 0;
                    $recalled = (int) $batch['is_recalled'] === 1;
                ?>
                <tr>
                    <td><?= e($batch['product_name']) ?><div style="font-size:12px;color:var(--color-grey);"><?= e($batch['sku']) ?></div></td>
                    <td><?= e($batch['batch_number']) ?></td>
                    <td><?= e(format_date($batch['expiry_date'])) ?></td>
                    <td><?= e(format_qty($batch['quantity_remaining'])) ?></td>
                    <td>
                        <?php if ($recalled): ?>
                            <span class="badge badge-danger">Recalled</span>
                        <?php elseif ($expired): ?>
                            <span class="badge badge-danger">Expired</span>
                        <?php elseif ($days !== null && $days <= 60): ?>
                            <span class="badge badge-amber">Expiring</span>
                        <?php else: ?>
                            <span class="badge badge-success">Sellable</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$recalled): ?>
                        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Recall this batch and block sales?" data-modal-title="Recall batch" data-action="<?= e(url('/inventory/batches/' . $batch['id'] . '/recall')) ?>" data-recall-reason="Manual recall">Recall</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
