<div class="page-head">
    <div><h2>Customers</h2><p>Retail patients and wholesale accounts.</p></div>
    <a class="btn" data-modal data-modal-title="New customer" href="<?= e(url('/customers/create')) ?>">New customer</a>
</div>
<div class="card">
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Credit</th><th>NHIS</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= e($customer->name) ?></td>
                    <td><span class="badge badge-navy"><?= e($customer->type) ?></span></td>
                    <td><?= e($customer->phone) ?></td>
                    <td><?= e(money($customer->credit_balance)) ?> / <?= e(money($customer->credit_limit)) ?></td>
                    <td><?= e($customer->nhis_number ?: '—') ?></td>
                    <td class="table-actions">
                        <a class="btn btn-outline btn-sm" data-modal data-modal-title="Edit customer" href="<?= e(url('/customers/' . $customer->id . '/edit')) ?>">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Archive this customer?" data-modal-title="Delete customer" data-action="<?= e(url('/customers/' . $customer->id)) ?>" data-method="DELETE">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/customers']); ?>
</div>
