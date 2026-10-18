<div class="page-head">
    <div>
        <h2>Stock transfers</h2>
        <p>Transfers sent from or received at your branch.</p>
    </div>
    <?php if ($canMutate): ?>
    <a class="btn" data-modal data-modal-title="Transfer stock" href="<?= e(url('/inventory/transfers/create')) ?>">Transfer stock</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data data-compact">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Lines</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transfers as $row): ?>
                <tr>
                    <td><?= e($row['transfer_number']) ?></td>
                    <td><?= e($row['from_name']) ?></td>
                    <td><?= e($row['to_name']) ?></td>
                    <td><?= (int) ($row['line_count'] ?? 0) ?></td>
                    <td><span class="badge badge-success"><?= e($row['status']) ?></span></td>
                    <td><?= e(format_datetime($row['created_at'])) ?></td>
                    <td class="table-actions table-actions-nowrap">
                        <a class="btn btn-outline btn-xs" data-modal data-modal-title="Transfer <?= e($row['transfer_number']) ?>" href="<?= e(url('/inventory/transfers/' . $row['id'])) ?>">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
