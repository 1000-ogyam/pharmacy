<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= e($customer ? 'Edit customer' : 'New customer') ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form class="modal-form" method="post" action="<?= e($customer ? url('/customers/' . $customer->id) : url('/customers')) ?>">
    <?= csrf_field() ?>
    <?php if ($customer): ?><?= method_field('PUT') ?><?php endif; ?>
    <?php if (!empty($returnTo)): ?>
        <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>
    <?php if ($isModal && !$customer): ?>
        <p class="modal-form-intro">Add a customer to your branch. They will appear in the quotation list after save.</p>
    <?php endif; ?>
    <div class="form-grid">
        <div class="form-row full"><label>Name</label><input name="name" value="<?= e(old('name', $customer?->name ?? '')) ?>" required></div>
        <div class="form-row"><label>Type</label>
            <select name="type">
                <?php $typeDefault = $customer?->type ?? ($defaultType ?? 'retail'); ?>
                <option value="retail" <?= $typeDefault === 'retail' ? 'selected' : '' ?>>Retail</option>
                <option value="wholesale" <?= $typeDefault === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
            </select>
        </div>
        <div class="form-row"><label>Phone</label><input name="phone" value="<?= e(old('phone', $customer?->phone ?? '')) ?>"></div>
        <div class="form-row"><label>Email</label><input name="email" value="<?= e(old('email', $customer?->email ?? '')) ?>"></div>
        <div class="form-row"><label>NHIS number</label><input name="nhis_number" value="<?= e(old('nhis_number', $customer?->nhis_number ?? '')) ?>"></div>
        <div class="form-row"><label>Credit limit</label><input name="credit_limit" type="number" step="0.01" value="<?= e(old('credit_limit', $customer?->credit_limit ?? 0)) ?>"></div>
        <div class="form-row"><label>Pricing tier</label>
            <select name="pricing_tier">
                <option value="wholesale_tier1">Wholesale tier 1</option>
                <option value="wholesale_tier2">Wholesale tier 2</option>
            </select>
        </div>
        <div class="form-row full"><label>Address</label><input name="address" value="<?= e(old('address', $customer?->address ?? '')) ?>"></div>
        <div class="form-row"><label><input type="checkbox" name="sms_opt_in" value="1" <?= ($customer?->sms_opt_in ?? 1) ? 'checked' : '' ?>> SMS opt-in</label></div>
    </div>
    <div class="modal-form-actions">
        <?php if ($isModal): ?>
            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <?php endif; ?>
        <button class="btn" type="submit">Save customer</button>
    </div>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
