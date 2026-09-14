<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= e($supplier ? 'Edit supplier' : 'New supplier') ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e($supplier ? url('/suppliers/' . $supplier->id) : url('/suppliers')) ?>">
    <?= csrf_field() ?>
    <?php if ($supplier): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="form-grid">
        <div class="form-row full"><label>Name</label><input name="name" value="<?= e($supplier?->name ?? '') ?>" required></div>
        <div class="form-row"><label>Contact</label><input name="contact_person" value="<?= e($supplier?->contact_person ?? '') ?>"></div>
        <div class="form-row"><label>Phone</label><input name="phone" value="<?= e($supplier?->phone ?? '') ?>"></div>
        <div class="form-row"><label>Email</label><input name="email" value="<?= e($supplier?->email ?? '') ?>"></div>
        <div class="form-row"><label>Currency</label><input name="currency_code" value="<?= e($supplier?->currency_code ?? 'GHS') ?>"></div>
        <div class="form-row"><label>Payment terms (days)</label><input name="payment_terms_days" type="number" value="<?= e((string) ($supplier?->payment_terms_days ?? 30)) ?>"></div>
        <div class="form-row full"><label>Address</label><input name="address" value="<?= e($supplier?->address ?? '') ?>"></div>
    </div>
    <button class="btn" style="margin-top:16px;">Save</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
