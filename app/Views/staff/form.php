<?php
/** @var \App\Models\User|null $staff */
$isModal = !empty($modal);
$editing = $staff instanceof \App\Models\User;
?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2><?= e($editing ? 'Edit staff' : 'Add staff') ?></h2></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e($editing ? url('/staff/' . $staff->id) : url('/staff')) ?>">
    <?= csrf_field() ?>
    <?php if ($editing): ?><?= method_field('PUT') ?><?php endif; ?>
    <div class="form-grid">
        <div class="form-row full"><label>Name</label><input name="name" value="<?= e(old('name', $staff?->name ?? '')) ?>" required></div>
        <div class="form-row"><label>Email</label><input type="email" name="email" value="<?= e(old('email', $staff?->email ?? '')) ?>" required></div>
        <div class="form-row"><label>Phone</label><input name="phone" value="<?= e(old('phone', $staff?->phone ?? '')) ?>"></div>
        <div class="form-row">
            <label><?= $editing ? 'New password' : 'Password' ?></label>
            <input type="password" name="password" autocomplete="new-password" <?= $editing ? '' : 'required' ?> minlength="8" placeholder="<?= $editing ? 'Leave blank to keep current' : '' ?>">
            <?php if ($editing): ?><div class="help" style="color:var(--color-muted);">Minimum 8 characters when changing.</div><?php endif; ?>
        </div>
        <div class="form-row"><label>Role</label>
            <select name="role_id" required>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int) $role->id ?>" <?= (int) old('role_id', $staff?->role_id ?? 0) === (int) $role->id ? 'selected' : '' ?>><?= e($role->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Branch</label>
            <select name="branch_id" required>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch['id'] ?>" <?= (int) old('branch_id', $staff?->branch_id ?? 0) === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Licence no.</label><input name="licence_number" value="<?= e(old('licence_number', $staff?->licence_number ?? '')) ?>"></div>
        <div class="form-row"><label>Licence expires</label><input type="date" name="licence_expires_at" value="<?= e(old('licence_expires_at', $staff?->licence_expires_at ?? '')) ?>"></div>
        <div class="form-row"><label>Portal customer</label>
            <select name="customer_id">
                <option value="">— None —</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int) $customer->id ?>" <?= (int) old('customer_id', $staff?->customer_id ?? 0) === (int) $customer->id ? 'selected' : '' ?>><?= e($customer->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Portal supplier</label>
            <select name="supplier_id">
                <option value="">— None —</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= (int) $supplier->id ?>" <?= (int) old('supplier_id', $staff?->supplier_id ?? 0) === (int) $supplier->id ? 'selected' : '' ?>><?= e($supplier->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row full">
            <label><input type="checkbox" name="is_active" value="1" <?= (int) old('is_active', $staff?->is_active ?? 1) ? 'checked' : '' ?>> Account active</label>
        </div>
        <?php if ($editing): ?>
        <div class="form-row full">
            <label><input type="checkbox" name="clear_login_lock" value="1"> Clear failed login lock</label>
        </div>
        <?php endif; ?>
    </div>
    <button class="btn" style="margin-top:16px;" type="submit"><?= $editing ? 'Save changes' : 'Save staff' ?></button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
