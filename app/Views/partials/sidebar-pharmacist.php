<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/dashboard', 'label' => 'Dashboard']); ?>
        <?php \App\Core\View::include('partials.nav-group', [
            'id' => 'dispensing',
            'label' => 'Dispensing',
            'icon' => 'M8 3h8v18H8zm3 5h2m-1 0v6',
            'links' => [
                ['href' => '/pos', 'label' => 'POS'],
                ['href' => '/prescriptions', 'label' => 'Prescriptions'],
                ['href' => '/nhis', 'label' => 'NHIS'],
                ['href' => '/inventory/batches', 'label' => 'Batches'],
                ['href' => '/customers', 'label' => 'Patients'],
            ],
        ]); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
