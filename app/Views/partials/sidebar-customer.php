<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'account',
            'label' => 'My account',
            'icon' => 'M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12zm-7 8a7 7 0 0 1 14 0',
            'links' => [
                ['href' => '/portal', 'label' => 'Overview'],
                ['href' => '/portal/orders', 'label' => 'Orders'],
                ['href' => '/portal/invoices', 'label' => 'Invoices'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
