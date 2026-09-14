<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>New purchase order</h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e(url('/purchase-orders')) ?>">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-row">
            <label>Supplier</label>
            <select name="supplier_id" required>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= (int) $supplier->id ?>"><?= e($supplier->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Currency</label><input name="currency_code" value="GHS" required></div>
        <div class="form-row"><label>Exchange rate to GHS</label><input name="exchange_rate_at_purchase" type="number" step="0.000001" value="1" required></div>
        <div class="form-row"><label>Expected date</label><input type="date" name="expected_date"></div>
    </div>
    <div class="line-editor" data-line-table>
        <div class="table-wrap">
            <table class="data line-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-cost">Unit cost</th>
                        <th class="col-num">Line total</th>
                        <th class="col-action"></th>
                    </tr>
                </thead>
                <tbody data-line-body></tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Estimated total</td>
                        <td data-line-grand>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <template data-line-template>
            <tr data-line-row>
                <td><?php \App\Core\View::include('partials.product-select', ['products' => $products]); ?></td>
                <td><input type="number" min="0" step="0.01" name="quantity[]" data-line-qty placeholder="0"></td>
                <td><input type="number" min="0" step="0.01" name="unit_cost[]" data-line-cost placeholder="0.00"></td>
                <td data-line-total class="muted">—</td>
                <td class="col-action"><button type="button" class="btn btn-outline btn-sm" data-line-remove>Remove</button></td>
            </tr>
        </template>
        <div class="line-editor-actions">
            <button type="button" class="btn btn-outline btn-sm" data-line-add>Add line</button>
        </div>
    </div>
    <button class="btn" style="margin-top:16px;">Create PO</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
