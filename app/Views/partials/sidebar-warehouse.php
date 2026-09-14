<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'warehouse',
            'label' => 'Warehouse',
            'icon' => 'M4 7l8-3 8 3-8 3zm0 0v10l8 3 8-3V7',
            'links' => [
                ['href' => '/inventory', 'label' => 'Stock'],
                ['href' => '/inventory/batches', 'label' => 'Batches'],
                ['href' => '/products', 'label' => 'Products'],
                ['href' => '/purchase-orders', 'label' => 'Purchase orders'],
                ['href' => '/suppliers', 'label' => 'Suppliers'],
                ['href' => '/returns', 'label' => 'Returns'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
