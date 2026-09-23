<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'sales',
            'label' => 'Sales',
            'icon' => 'M6 7h12l-1 11H7L6 7zm3-3h6l1 3H8l1-3z',
            'links' => [
                ['href' => '/pos', 'label' => 'Retail POS'],
                ['href' => '/sales', 'label' => 'Sales history'],
                ['href' => '/wholesale', 'label' => 'Wholesale'],
                ['href' => '/customers', 'label' => 'Customers'],
                ['href' => '/credit', 'label' => 'Credit'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'inventory',
            'label' => 'Inventory',
            'icon' => 'M4 7l8-3 8 3-8 3zm0 0v10l8 3 8-3V7',
            'links' => [
                ['href' => '/products', 'label' => 'Products'],
                ['href' => '/inventory', 'label' => 'Stock levels'],
                ['href' => '/inventory/batches', 'label' => 'Batches'],
                ['href' => '/purchase-orders', 'label' => 'Purchase orders'],
                ['href' => '/suppliers', 'label' => 'Suppliers'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'clinical',
            'label' => 'Clinical',
            'icon' => 'M12 3l8 4v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V7z',
            'links' => [
                ['href' => '/prescriptions', 'label' => 'Prescriptions'],
                ['href' => '/nhis', 'label' => 'NHIS claims'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'operations',
            'label' => 'Operations',
            'icon' => 'M3 16V8h11v8H3zm11-4h4l3 3v1h-7z',
            'links' => [
                ['href' => '/deliveries', 'label' => 'Deliveries'],
                ['href' => '/returns', 'label' => 'Returns'],
                ['href' => '/approvals', 'label' => 'Approvals'],
                ['href' => '/archives', 'label' => 'Archives'],
                ['href' => '/staff', 'label' => 'Staff'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'finance',
            'label' => 'Finance',
            'icon' => 'M5 19V9m7 10V5m7 14v-7',
            'links' => [
                ['href' => '/accounting', 'label' => 'Accounting'],
                ['href' => '/reports', 'label' => 'Reports'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
