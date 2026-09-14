<div class="page-head"><div><h2>Approvals</h2></div></div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>Type</th><th>Record</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row->type) ?></td>
            <td><?= e($row->record_type) ?> #<?= e((string) $row->record_id) ?></td>
            <td><span class="badge badge-amber"><?= e($row->status) ?></span></td>
            <td>
                <button type="button" class="btn btn-sm" data-modal-confirm="Approve this request?" data-modal-title="Approve" data-action="<?= e(url('/approvals/' . $row->id)) ?>" data-status="approved">Approve</button>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if ($rows === []): ?>
        <tr><td colspan="4" class="empty">No pending approvals.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
