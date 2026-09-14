<div class="page-head">
    <div><h2>Suppliers</h2></div>
    <a class="btn" data-modal data-modal-title="New supplier" href="<?= e(url('/suppliers/create')) ?>">New supplier</a>
</div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>Name</th><th>Contact</th><th>Currency</th><th>Terms</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($suppliers as $supplier): ?>
        <tr>
            <td><?= e($supplier->name) ?></td>
            <td><?= e($supplier->contact_person) ?> · <?= e($supplier->phone) ?></td>
            <td><?= e($supplier->currency_code) ?></td>
            <td><?= e((string) $supplier->payment_terms_days) ?> days</td>
            <td class="table-actions">
                <a class="btn btn-outline btn-sm" data-modal data-modal-title="Edit supplier" href="<?= e(url('/suppliers/' . $supplier->id . '/edit')) ?>">Edit</a>
                <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Archive this supplier?" data-modal-title="Delete supplier" data-action="<?= e(url('/suppliers/' . $supplier->id)) ?>" data-method="DELETE">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/suppliers']); ?>
</div>
