<div class="page-head">
    <div><h2>Prescriptions</h2></div>
    <a class="btn" data-modal data-modal-title="New prescription" data-modal-wide href="<?= e(url('/prescriptions/create')) ?>">New prescription</a>
</div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>No.</th><th>Patient</th><th>Prescriber</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['prescription_number']) ?></td>
            <td><?= e($row['customer_name']) ?></td>
            <td><?= e($row['prescribed_by']) ?></td>
            <td><span class="badge badge-teal"><?= e($row['status']) ?></span></td>
            <td>
                <a class="btn btn-outline btn-sm" data-modal data-modal-title="<?= e($row['prescription_number']) ?>" data-modal-wide href="<?= e(url('/prescriptions/' . $row['id'])) ?>">Open</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
