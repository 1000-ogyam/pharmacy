<div class="page-head">
    <div><h2>Staff & licences</h2><p>Create accounts, reset passwords, and manage branch access.</p></div>
    <?php if (auth()->hasRole('admin')): ?>
    <a class="btn" data-modal data-modal-title="Add staff" data-modal-wide href="<?= e(url('/staff/create')) ?>">Add staff</a>
    <?php endif; ?>
</div>
<div class="card"><div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Role</th><th>Branch</th><th>Licence</th><th>Expires</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
                $days = days_until($row['licence_expires_at']);
                $isSelf = (int) ($row['id'] ?? 0) === (int) (auth()->id() ?? 0);
            ?>
            <tr>
                <td><?= e($row['name']) ?><div style="font-size:12px;color:var(--color-grey);"><?= e($row['email']) ?></div></td>
                <td><?= e($row['role_name']) ?></td>
                <td><?= e($row['branch_name']) ?></td>
                <td><?= e($row['licence_number'] ?: '—') ?></td>
                <td>
                    <?= e(format_date($row['licence_expires_at'])) ?>
                    <?php if ($days !== null && $days <= 60): ?><span class="badge badge-amber">Renew</span><?php endif; ?>
                </td>
                <td>
                    <?php if ((int) ($row['is_active'] ?? 0) === 1): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php endif; ?>
                    <?php if (!empty($row['locked_until']) && strtotime((string) $row['locked_until']) > time()): ?>
                        <span class="badge badge-amber">Locked</span>
                    <?php endif; ?>
                </td>
                <td class="table-actions">
                    <a class="btn btn-outline btn-sm" data-modal data-modal-wide data-modal-title="Edit staff" href="<?= e(url('/staff/' . $row['id'] . '/edit')) ?>">Edit</a>
                    <?php if (!$isSelf && auth()->hasRole('admin')): ?>
                    <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Permanently delete this staff account? This cannot be undone." data-modal-title="Delete staff" data-action="<?= e(url('/staff/' . $row['id'])) ?>" data-method="DELETE">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/staff']); ?>
</div>
