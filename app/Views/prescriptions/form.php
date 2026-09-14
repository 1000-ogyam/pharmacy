<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>New prescription</h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e(url('/prescriptions')) ?>">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-row">
            <label>Patient</label>
            <select name="customer_id" required>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int) $customer->id ?>"><?= e($customer->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Prescriber</label><input name="prescribed_by"></div>
        <div class="form-row full"><label>Diagnosis</label><input name="diagnosis"></div>
    </div>
    <div class="line-editor" data-line-table>
        <div class="table-wrap">
            <table class="data line-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Dosage</th>
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
                <td><input name="dosage[]" placeholder="e.g. 1 tab twice daily"></td>
                <td><input type="number" min="0" step="0.01" name="quantity[]" placeholder="0"></td>
                <td class="col-action"><button type="button" class="btn btn-outline btn-sm" data-line-remove>Remove</button></td>
            </tr>
        </template>
        <div class="line-editor-actions">
            <button type="button" class="btn btn-outline btn-sm" data-line-add>Add line</button>
        </div>
    </div>
    <button class="btn" style="margin-top:16px;">Save</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
