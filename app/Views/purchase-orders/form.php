<?php
/**
 * @var \App\Models\PurchaseOrder|null $order
 * @var list<array{product_id: int, quantity: float, unit_cost: float}> $initialLines
 */
$isModal = !empty($modal);
$isEdit = isset($order) && $order instanceof \App\Models\PurchaseOrder;
$selectedSupplierId = (int) old('supplier_id', (string) ($preselectSupplierId ?? 0));
$formAction = $isEdit
    ? url('/purchase-orders/' . $order->id)
    : url('/purchase-orders');
$initialLinesJson = json_encode($initialLines ?? [], JSON_THROW_ON_ERROR);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= $isEdit ? 'Edit purchase order' : 'New purchase order' ?></h2>
        <p><?= $isEdit ? e($order->po_number) : 'Order stock from a supplier. Receipt increases on-hand quantity.' ?></p>
    </div>
    <?php if ($isEdit): ?>
        <a class="btn btn-outline" href="<?= e(url('/purchase-orders/' . $order->id)) ?>">View PO</a>
    <?php endif; ?>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form
    class="modal-form"
    method="post"
    action="<?= e($formAction) ?>"
    data-purchase-order
    data-initial-lines="<?= e($initialLinesJson) ?>"
>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>
    <?php if ($isModal): ?>
        <p class="modal-form-intro"><?= $isEdit ? 'Update supplier, lines, or costs, then save.' : 'Pick a supplier and lines. Unit cost defaults from the last purchase or batch; change it if the price is different.' ?></p>
    <?php endif; ?>
    <div class="form-row">
        <label for="po-supplier">Supplier</label>
        <select id="po-supplier" name="supplier_id" required>
            <option value="">Select supplier</option>
            <?php foreach ($suppliers as $supplier): ?>
                <?php $selected = $selectedSupplierId === (int) $supplier->id ? ' selected' : ''; ?>
                <option value="<?= (int) $supplier->id ?>"<?= $selected ?>><?= e($supplier->name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-row">
        <label for="po-expected">Expected date</label>
        <input id="po-expected" type="date" name="expected_date" value="<?= e(old('expected_date', $isEdit ? (string) ($order->expected_date ?? '') : '')) ?>">
    </div>
    <div class="line-editor" data-line-table data-purchase-order-table>
        <div class="table-wrap">
            <table class="data line-table line-table-compact">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="col-qty">Quantity</th>
                        <th class="col-price">Unit cost</th>
                        <th class="col-amount">Amount</th>
                        <th class="col-action"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody data-line-body></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="col-amount" data-line-grand>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <template data-line-template>
            <tr data-line-row>
                <td><?php \App\Core\View::include('partials.product-select', ['products' => $products, 'lineCosts' => $lineCosts ?? []]); ?></td>
                <td class="col-qty"><input type="number" min="0" step="0.01" name="quantity[]" data-line-qty placeholder="0"></td>
                <td class="col-price"><input type="number" min="0" step="0.01" name="unit_cost[]" data-line-cost class="line-cost-input" placeholder="0.00"></td>
                <td class="col-amount muted" data-line-amount>—</td>
                <td class="col-action">
                    <button type="button" class="btn-icon btn-icon-danger" data-line-remove aria-label="Remove line">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 4v6m4-6v6m2-10h-8l1 14h6l1-14"/></svg>
                    </button>
                </td>
            </tr>
        </template>
        <div class="line-editor-actions">
            <button type="button" class="btn btn-outline btn-sm btn-with-icon" data-line-add aria-label="Add line">
                <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Add line
            </button>
        </div>
    </div>
    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit"><?= $isEdit ? 'Update PO' : 'Create PO' ?></button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
