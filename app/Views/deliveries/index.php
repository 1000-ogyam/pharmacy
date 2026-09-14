<div class="page-head">
    <div><h2>Deliveries</h2></div>
    <button type="button" class="btn" data-modal-src="#delivery-form" data-modal-title="Schedule delivery">Schedule delivery</button>
</div>
<template id="delivery-form">
    <form method="post" action="<?= e(url('/deliveries')) ?>">
        <?= csrf_field() ?>
        <div class="form-row"><label>Customer</label>
            <select name="customer_id" required>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= (int) $customer->id ?>"><?= e($customer->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row" style="margin-top:8px;"><label>Driver</label><input name="driver_name"></div>
        <div class="form-row" style="margin-top:8px;"><label>Address</label><input name="address"></div>
        <div class="form-row" style="margin-top:8px;"><label>Scheduled</label><input type="datetime-local" name="scheduled_at"></div>
        <button class="btn" style="margin-top:12px;">Schedule</button>
    </form>
</template>
<div class="card"><div class="table-wrap">
    <table class="data">
        <thead><tr><th>Customer</th><th>Driver</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e($row['customer_name']) ?></td>
                <td><?= e($row['driver_name']) ?></td>
                <td><span class="badge badge-teal"><?= e($row['status']) ?></span></td>
                <td>
                    <?php if ($row['status'] !== 'delivered'): ?>
                    <button type="button" class="btn btn-sm" data-modal-confirm="Mark this delivery as completed?" data-modal-title="Complete delivery" data-action="<?= e(url('/deliveries/' . $row['id'] . '/complete')) ?>">Delivered</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages]); ?>
</div>
