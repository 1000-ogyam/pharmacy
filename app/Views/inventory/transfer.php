<?php $isModal = !empty($modal); ?>
<?php if (!$isModal): ?>
<div class="page-head"><div><h2>Transfer stock</h2><p>Uses FEFO from the current branch.</p></div></div>
<div class="card"><div class="card-body">
<?php endif; ?>
<form method="post" action="<?= e(url('/inventory/transfers')) ?>">
    <?= csrf_field() ?>
    <div class="form-grid">
        <div class="form-row">
            <label>Destination branch</label>
            <select name="to_branch_id" required>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?= (int) $branch['id'] ?>"><?= e($branch['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label>Product</label>
            <select name="product_id" required>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id'] ?>"><?= e($product['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label>Quantity</label><input type="number" step="0.01" name="quantity" required></div>
    </div>
    <button class="btn" style="margin-top:16px;">Transfer</button>
</form>
<?php if (!$isModal): ?>
</div></div>
<?php endif; ?>
