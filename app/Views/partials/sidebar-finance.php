<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'finance',
            'label' => 'Finance',
            'icon' => 'M6 4h12v16H6zm3 4h6M9 12h6M9 16h4',
            'links' => [
                ['href' => '/accounting', 'label' => 'Accounting'],
                ['href' => '/credit', 'label' => 'Credit / AR'],
                ['href' => '/reports', 'label' => 'Reports'],
                ['href' => '/customers', 'label' => 'Customers'],
                ['href' => '/suppliers', 'label' => 'Suppliers'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
