<div class="page-head">
    <div><h2>Wholesale</h2><p>Quotation → order → invoice, with credit-limit checks.</p></div>
    <a class="btn" data-modal data-modal-title="New quotation" data-modal-wide href="<?= e(url('/wholesale/quotations/create')) ?>">New quotation</a>
</div>
<div class="grid grid-2">
    <div class="card">
        <div class="card-head">Quotations</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>No.</th><th>Customer</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td><?= e($quote['quotation_number']) ?></td>
                        <td><?= e($quote['customer_name']) ?></td>
                        <td><?= e(money($quote['total'])) ?></td>
                        <td><span class="badge badge-navy"><?= e($quote['status']) ?></span></td>
                        <td class="table-actions">
                            <?php if ($quote['status'] !== 'converted'): ?>
                            <button type="button" class="btn btn-sm" data-modal-confirm="Convert this quotation to an order and invoice?" data-modal-title="Convert quotation" data-method="POST" data-action="<?= e(url('/wholesale/quotations/' . $quote['id'] . '/convert')) ?>">Convert</button>
                            <?php endif; ?>
                            <?php if (auth()->hasRole('admin')): ?>
                            <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Archive this quotation?" data-modal-title="Delete quotation" data-action="<?= e(url('/wholesale/quotations/' . $quote['id'])) ?>" data-method="DELETE">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $quotePage, 'pages' => $quotePages, 'pageParam' => 'qpage']); ?>
    </div>
    <div class="card">
        <div class="card-head">Orders</div>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>No.</th><th>Customer</th><th>Total</th><th>Credit used</th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e(money($order['total'])) ?></td>
                        <td><?= e(money($order['credit_used'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
    </div>
</div>
