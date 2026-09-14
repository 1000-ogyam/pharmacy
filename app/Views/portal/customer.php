<div class="page-head"><div><h2>Customer portal</h2><p>Invoices and statements for the logged-in account.</p></div></div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>Invoice</th><th>Type</th><th>Total</th><th>Due</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($invoices as $invoice): ?>
        <tr>
            <td><?= e($invoice['invoice_number']) ?></td>
            <td><?= e($invoice['invoice_type']) ?></td>
            <td><?= e(money($invoice['total'])) ?></td>
            <td><?= e(format_date($invoice['due_date'])) ?></td>
            <td><?= e($invoice['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
