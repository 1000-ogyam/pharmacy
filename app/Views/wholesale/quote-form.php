<?php
/** @var int $preselectCustomerId */
$isModal = !empty($modal);
$selectedCustomerId = (int) old('customer_id', (string) ($preselectCustomerId ?? 0));
$returnTo = '/wholesale/quotations/create';
?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>New quotation</h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e(url('/wholesale/quotations')) ?>">
    <?= csrf_field() ?>
    <div class="form-row">
        <label>Customer</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;">
            <select name="customer_id" required style="flex:1;min-width:200px;">
                <option value="">— Select customer —</option>
                <?php foreach ($customers as $customer): ?>
                    <?php
                        $creditLimit = (float) $customer->credit_limit;
                        $creditHint = $creditLimit > 0
                            ? ' · credit ' . money($customer->availableCredit()) . ' avail'
                            : ' · no credit account';
                        $inactiveHint = !(int) $customer->is_active ? ' · inactive' : '';
                        $selected = $selectedCustomerId === (int) $customer->id ? ' selected' : '';
                    ?>
                    <option value="<?= (int) $customer->id ?>"<?= $selected ?>><?= e($customer->name) ?> (<?= e($customer->type) ?><?= e($creditHint) ?><?= e($inactiveHint) ?>)</option>
                <?php endforeach; ?>
            </select>
            <?php if ($canMutate): ?>
            <a class="btn btn-outline btn-sm" data-modal data-modal-title="New customer" href="<?= e(url('/customers/create?return_to=' . rawurlencode($returnTo))) ?>">Add customer</a>
            <?php endif; ?>
        </div>
        <?php if (count($customers) === 0): ?>
            <div class="help" style="color:var(--color-muted);margin-top:8px;">No customers at this branch yet. Use <strong>Add customer</strong> above.</div>
        <?php endif; ?>
    </div>
    <div class="line-editor" data-line-table>
        <div class="table-wrap">
            <table class="data line-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="col-num">In stock</th>
                        <th class="col-qty">Qty</th>
                        <th class="col-action"></th>
                    </tr>
                </thead>
                <tbody data-line-body></tbody>
            </table>
        </div>
        <template data-line-template>
            <tr data-line-row>
                <td><?php \App\Core\View::include('partials.product-select', ['products' => $products]); ?></td>
                <td data-line-stock class="muted">—</td>
                <td><input type="number" min="0" step="0.01" name="quantity[]" placeholder="0"></td>
                <td class="col-action"><button type="button" class="btn btn-outline btn-sm" data-line-remove>Remove</button></td>
            </tr>
        </template>
        <div class="line-editor-actions">
            <button type="button" class="btn btn-outline btn-sm" data-line-add>Add line</button>
        </div>
    </div>
    <button class="btn" style="margin-top:16px;">Save quotation</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
