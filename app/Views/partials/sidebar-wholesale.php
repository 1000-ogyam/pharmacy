<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'wholesale',
            'label' => 'Wholesale',
            'icon' => 'M4 10h16v9H4zm2-5h12l2 5H4z',
            'links' => [
                ['href' => '/wholesale', 'label' => 'Orders'],
                ['href' => '/customers', 'label' => 'Accounts'],
                ['href' => '/credit', 'label' => 'Credit limits'],
                ['href' => '/deliveries', 'label' => 'Deliveries'],
                ['href' => '/products', 'label' => 'Catalogue'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
