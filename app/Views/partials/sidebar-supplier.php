<aside class="sidebar">
    <?php \App\Core\View::include('partials.sidebar-brand'); ?>
    <nav>
        <?php \App\Core\View::include('partials.nav-link', ['href' => '/supplier-portal', 'label' => 'Purchase orders']); ?>
        <?php \App\Core\View::include('partials.nav-help'); ?>
    </nav>
</aside>
