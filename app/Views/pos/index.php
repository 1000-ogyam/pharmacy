<div class="page-head">
    <div>
        <p class="eyebrow">Counter</p>
        <h2>Retail POS</h2>
        <p>FEFO picks the batch. Expired or recalled stock stays off the ticket.</p>
    </div>
</div>

<div class="pos-layout" data-pos>
    <div class="card pos-catalogue">
        <div class="card-body">
            <div class="pos-toolbar">
                <form class="searchbar" method="get" action="<?= e(url('/pos')) ?>">
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, SKU, barcode…">
                    <button class="btn" type="submit">Search</button>
                </form>
                <div class="view-toggle" role="group" aria-label="Product layout">
                    <button type="button" class="icon-btn is-active" data-pos-view="grid" title="Grid view" aria-label="Grid view">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" d="M4 4h7v7H4zm9 0h7v7h-7zM4 13h7v7H4zm9 0h7v7h-7z"/></svg>
                    </button>
                    <button type="button" class="icon-btn" data-pos-view="list" title="List view" aria-label="List view">
                        <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                </div>
            </div>
            <div class="pos-products is-grid" data-pos-products>
                <?php foreach ($products as $product): ?>
                    <button
                        type="button"
                        class="pos-product"
                        data-pos-add
                        data-id="<?= (int) $product['id'] ?>"
                        data-name="<?= e($product['name']) ?>"
                        data-price="<?= e((string) $product['price']) ?>"
                        data-stock="<?= e((string) ($product['stock'] ?? 0)) ?>"
                    >
                        <div class="pos-product-main">
                            <strong><?= e($product['name']) ?></strong>
                            <span class="pos-product-meta"><?= e($product['sku']) ?> · <?= e($product['strength'] ?: $product['dosage_form']) ?></span>
                        </div>
                        <div class="pos-product-side">
                            <span class="pos-product-price"><?= e(money($product['price'])) ?></span>
                            <span class="pos-product-stock">Stock <?= e(format_qty($product['stock'] ?? 0)) ?></span>
                        </div>
                    </button>
                <?php endforeach; ?>
                <?php if ($products === []): ?>
                    <div class="empty">No products match that search.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card pos-cart-card">
        <div class="card-head">Cart</div>
        <div class="card-body">
            <div data-pos-cart></div>
            <form method="post" action="<?= e(url('/pos/sale')) ?>" data-pos-form>
                <?= csrf_field() ?>
                <input type="hidden" name="items" value="[]" data-pos-items>
                <div class="form-row" style="margin-top:12px;">
                    <label>Customer</label>
                    <select name="customer_id">
                        <option value="">Walk-in</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer->id ?>"><?= e($customer->name) ?> (<?= e($customer->type) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row" style="margin-top:10px;">
                    <label>Payment</label>
                    <select name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="card">Card</option>
                        <option value="credit">Credit</option>
                    </select>
                </div>
                <div class="form-row" style="margin-top:10px;">
                    <label>Discount (GHS)</label>
                    <input type="number" step="0.01" name="discount" value="0" data-pos-discount>
                </div>
                <div class="totals">
                    <div><span>Subtotal</span><span data-pos-subtotal>GHS 0.00</span></div>
                    <div><span>Discount</span><span data-pos-discount-label>GHS 0.00</span></div>
                    <div class="grand"><span>Total</span><span data-pos-total>GHS 0.00</span></div>
                </div>
                <button class="btn btn-block btn-lg" style="margin-top:14px;" type="submit" data-pos-submit disabled>Complete sale</button>
            </form>
        </div>
    </div>
</div>
