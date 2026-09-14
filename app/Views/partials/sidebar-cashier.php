<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'retail',
            'label' => 'Retail',
            'icon' => 'M6 7h12l-1 11H7L6 7zm3-3h6l1 3H8l1-3z',
            'links' => [
                ['href' => '/pos', 'label' => 'POS'],
                ['href' => '/customers', 'label' => 'Customers'],
                ['href' => '/reports', 'label' => 'My sales'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
