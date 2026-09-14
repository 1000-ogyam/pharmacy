<div class="page-head">
    <div><h2>Staff & licences</h2></div>
    <button type="button" class="btn" data-modal-src="#staff-form" data-modal-title="Add staff">Add staff</button>
</div>
<template id="staff-form">
    <form method="post" action="<?= e(url('/staff')) ?>">
        <?= csrf_field() ?>
        <div class="form-row"><label>Name</label><input name="name" required></div>
        <div class="form-row" style="margin-top:8px;"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-row" style="margin-top:8px;"><label>Password</label><input type="password" name="password" required></div>
        <div class="form-row" style="margin-top:8px;"><label>Role</label>
            <select name="role_id"><?php foreach ($roles as $role): ?><option value="<?= (int) $role->id ?>"><?= e($role->name) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-row" style="margin-top:8px;"><label>Branch</label>
            <select name="branch_id"><?php foreach ($branches as $branch): ?><option value="<?= (int) $branch['id'] ?>"><?= e($branch['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="form-row" style="margin-top:8px;"><label>Licence no.</label><input name="licence_number"></div>
        <div class="form-row" style="margin-top:8px;"><label>Licence expires</label><input type="date" name="licence_expires_at"></div>
        <button class="btn" style="margin-top:12px;">Save staff</button>
    </form>
</template>
<div class="card"><div class="table-wrap">
    <table class="data">
        <thead><tr><th>Name</th><th>Role</th><th>Licence</th><th>Expires</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php $days = days_until($row['licence_expires_at']); ?>
            <tr>
                <td><?= e($row['name']) ?><div style="font-size:12px;color:var(--color-grey);"><?= e($row['email']) ?></div></td>
                <td><?= e($row['role_name']) ?></td>
                <td><?= e($row['licence_number'] ?: '—') ?></td>
                <td>
                    <?= e(format_date($row['licence_expires_at'])) ?>
                    <?php if ($days !== null && $days <= 60): ?><span class="badge badge-amber">Renew</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
