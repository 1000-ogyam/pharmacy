<div class="page-head">
    <div>
        <h2>Wholesale</h2>
        <p>Quotations, orders, and credit at your branch.</p>
    </div>
    <?php if ($canMutate): ?>
    <a class="btn" data-modal data-modal-title="New quotation" data-modal-wide href="<?= e(url('/wholesale/quotations/create')) ?>">New quotation</a>
    <?php endif; ?>
</div>
<div class="wholesale-board grid grid-2">
    <div class="card wholesale-panel">
        <div class="card-head">
            <span>Quotations</span>
        </div>
        <div class="table-wrap">
            <table class="data data-compact wholesale-table">
                <thead><tr><th>No.</th><th>Customer</th><th class="col-num">Total</th><th class="col-status">Status</th><th class="col-actions"></th></tr></thead>
                <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/wholesale/quotations/' . $quote['id'])) ?>" data-modal data-modal-wide data-modal-title="Quotation <?= e($quote['quotation_number']) ?>">
                                <?= e($quote['quotation_number']) ?>
                            </a>
                        </td>
                        <td class="col-customer"><?= e($quote['customer_name']) ?></td>
                        <td class="col-num"><?= e(money($quote['total'])) ?></td>
                        <td class="col-status"><span class="badge badge-navy"><?= e($quote['status']) ?></span></td>
                        <td class="col-actions">
                            <div class="table-actions table-actions-nowrap">
                            <?php $quoteEditable = $canMutate && (string) $quote['status'] !== 'converted'; ?>
                            <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="Quotation <?= e($quote['quotation_number']) ?>" href="<?= e(url('/wholesale/quotations/' . $quote['id'])) ?>">View</a>
                            <?php if ($quoteEditable): ?>
                            <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="Edit quotation" href="<?= e(url('/wholesale/quotations/' . $quote['id'] . '/edit')) ?>">Edit</a>
                            <button type="button" class="btn btn-outline btn-xs" data-modal-confirm="Convert this quotation to an order and invoice?" data-modal-title="Convert quotation" data-method="POST" data-action="<?= e(url('/wholesale/quotations/' . $quote['id'] . '/convert')) ?>">Convert</button>
                            <?php endif; ?>
                            <?php if ($canDelete): ?>
                            <button type="button" class="btn btn-danger btn-xs" data-modal-confirm="Archive this quotation?" data-modal-title="Archive quotation" data-confirm-danger data-action="<?= e(url('/wholesale/quotations/' . $quote['id'])) ?>" data-method="DELETE">Archive</button>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($quotes === []): ?>
                    <tr><td colspan="5" class="empty">No quotations yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $quotePage, 'pages' => $quotePages, 'pageParam' => 'qpage']); ?>
    </div>
    <div class="card wholesale-panel">
        <div class="card-head">
            <span>Orders</span>
        </div>
        <div class="table-wrap">
            <table class="data data-compact wholesale-table">
                <thead><tr><th>No.</th><th>Customer</th><th class="col-num">Total</th><th class="col-num">Credit</th><th class="col-actions"></th></tr></thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('/wholesale/orders/' . $order['id'])) ?>" data-modal data-modal-wide data-modal-title="Order <?= e($order['order_number']) ?>">
                                <?= e($order['order_number']) ?>
                            </a>
                        </td>
                        <td class="col-customer"><?= e($order['customer_name']) ?></td>
                        <td class="col-num"><?= e(money($order['total'])) ?></td>
                        <td class="col-num"><?= e(money($order['credit_used'])) ?></td>
                        <td class="col-actions">
                            <div class="table-actions table-actions-nowrap">
                            <a class="btn btn-outline btn-xs" data-modal data-modal-wide data-modal-title="Order <?= e($order['order_number']) ?>" href="<?= e(url('/wholesale/orders/' . $order['id'])) ?>">View</a>
                            <a class="btn btn-outline btn-xs" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order['id'] . '/invoice?print=1')) ?>">Print</a>
                            <a class="btn btn-outline btn-xs" target="_blank" rel="noopener" href="<?= e(url('/wholesale/orders/' . $order['id'] . '/invoice/pdf')) ?>">PDF</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($orders === []): ?>
                    <tr><td colspan="5" class="empty">No orders yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
    </div>
</div>
