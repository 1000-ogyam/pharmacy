<div class="page-head"><div><h2>Supplier portal</h2></div></div>
<div class="card"><div class="table-wrap">
<table class="data">
    <thead><tr><th>PO</th><th>Supplier</th><th>Status</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><?= e($order['po_number']) ?></td>
            <td><?= e($order['supplier_name']) ?></td>
            <td><?= e($order['status']) ?></td>
            <td><?= e(money($order['total_ghs'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
