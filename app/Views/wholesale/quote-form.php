<?php
/**
 * @var int $preselectCustomerId
 * @var \App\Models\Quotation|null $quote
 * @var list<array{product_id: int, quantity: float}> $initialLines
 */
$isModal = !empty($modal);
$isEdit = isset($quote) && $quote instanceof \App\Models\Quotation;
$selectedCustomerId = (int) old('customer_id', (string) ($preselectCustomerId ?? 0));
$returnTo = $returnTo ?? '/wholesale/quotations/create';
$formAction = $isEdit
    ? url('/wholesale/quotations/' . $quote->id)
    : url('/wholesale/quotations');
$initialLinesJson = json_encode($initialLines ?? [], JSON_THROW_ON_ERROR);
?>
<?php if (!$isModal): ?>
<div class="page-head">
    <div>
        <h2><?= $isEdit ? 'Edit quotation' : 'New quotation' ?></h2>
        <p><?= $isEdit ? e($quote->quotation_number) : 'Create a quote for a wholesale customer.' ?></p>
    </div>
    <?php if ($isEdit): ?>
        <a class="btn btn-outline" href="<?= e(url('/wholesale/quotations/' . $quote->id)) ?>">View quotation</a>
    <?php endif; ?>
</div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form
    class="modal-form"
    method="post"
    action="<?= e($formAction) ?>"
    data-wholesale-quote
    data-initial-lines="<?= e($initialLinesJson) ?>"
>
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><?= method_field('PUT') ?><?php endif; ?>
    <?php if ($isModal): ?>
        <p class="modal-form-intro"><?= $isEdit ? 'Update customer, lines, or quantities, then save.' : 'Pick a customer, add products and quantities, then save. Credit is checked when you convert to an order.' ?></p>
    <?php endif; ?>
    <div class="form-row">
        <label for="quote-customer">Customer</label>
        <div class="form-row-inline">
            <select id="quote-customer" name="customer_id" required>
                <option value="">Select customer</option>
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
            <a class="btn btn-outline btn-sm" data-modal data-modal-title="New customer" data-modal-wide href="<?= e(url('/customers/create?return_to=' . rawurlencode($returnTo))) ?>">Add customer</a>
            <?php endif; ?>
        </div>
        <?php if (count($customers) === 0): ?>
            <p class="form-hint">No customers yet. Use <strong>Add customer</strong>.</p>
        <?php endif; ?>
    </div>
    <div class="line-editor" data-line-table data-wholesale-quote-table>
        <div class="table-wrap">
            <table class="data line-table line-table-compact">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="col-stock">In stock</th>
                        <th class="col-qty">Quantity</th>
                        <th class="col-price">Unit price</th>
                        <th class="col-amount">Amount</th>
                        <th class="col-action"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody data-line-body></tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Total</td>
                        <td class="col-amount" data-line-grand>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <template data-line-template>
            <tr data-line-row>
                <td><?php \App\Core\View::include('partials.product-select', ['products' => $products, 'linePrices' => $linePrices ?? []]); ?></td>
                <td class="col-stock muted" data-line-stock>—</td>
                <td class="col-qty"><input type="number" min="0" step="0.01" name="quantity[]" data-line-qty placeholder="0"></td>
                <td class="col-price muted" data-line-unit-price>—</td>
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
        <button class="btn" type="submit"><?= $isEdit ? 'Update quotation' : 'Save quotation' ?></button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
