<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= e($customer ? 'Edit customer' : 'New customer') ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e($customer ? url('/customers/' . $customer->id) : url('/customers')) ?>">
    <?= csrf_field() ?>
    <?php if ($customer): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="form-grid">
        <div class="form-row full"><label>Name</label><input name="name" value="<?= e(old('name', $customer?->name ?? '')) ?>" required></div>
        <div class="form-row"><label>Type</label>
            <select name="type">
                <option value="retail" <?= ($customer?->type ?? '') === 'retail' ? 'selected' : '' ?>>Retail</option>
                <option value="wholesale" <?= ($customer?->type ?? '') === 'wholesale' ? 'selected' : '' ?>>Wholesale</option>
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
    <button class="btn" style="margin-top:16px;">Save</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
