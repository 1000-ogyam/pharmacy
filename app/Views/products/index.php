<div class="page-head">
    <div>
        <h2>Products</h2>
        <p>Catalogue, packaging units, and pricing.</p>
    </div>
    <?php if ($canMutate): ?>
    <a class="btn" data-modal data-modal-title="New product" data-modal-wide href="<?= e(url('/products/create')) ?>">New product</a>
    <?php endif; ?>
</div>
<div class="card">
    <div class="card-body">
        <form class="searchbar" method="get" data-live-search data-live-search-target="[data-live-results]">
            <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search products" autocomplete="off">
        </form>
    </div>
    <div data-live-results>
    <div class="table-wrap">
        <table class="data">
            <thead><tr><th>SKU</th><th>Name</th><th>Category</th><th>Rx</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= e($product->sku) ?></td>
                    <td><?= e($product->name) ?><div style="color:var(--color-grey);font-size:12px;"><?= e($product->generic_name) ?></div></td>
                    <td><?= e($product->category) ?></td>
                    <td><?= (int) $product->requires_prescription ? '<span class="badge badge-amber">Rx</span>' : '<span class="badge badge-grey">OTC</span>' ?></td>
                    <td class="table-actions">
                        <?php if ($canMutate): ?>
                        <a class="btn btn-outline btn-sm" data-modal data-modal-title="Edit product" data-modal-wide href="<?= e(url('/products/' . $product->id . '/edit')) ?>">Edit</a>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                        <button type="button" class="btn btn-danger btn-sm" data-modal-confirm="Permanently delete this product? This cannot be undone." data-modal-title="Delete product" data-action="<?= e(url('/products/' . $product->id)) ?>" data-method="DELETE">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php \App\Core\View::include('partials.pagination', ['page' => $page, 'pages' => $pages, 'base' => '/products']); ?>
    </div>
</div>
