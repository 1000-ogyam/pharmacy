<?php
$sidebar = $sidebar ?? 'partials.sidebar-admin';
ob_start();
?>
<div class="app-shell">
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    <?php \App\Core\View::include($sidebar, get_defined_vars()); ?>
    <div class="main">
        <?php \App\Core\View::include('partials.navbar', get_defined_vars()); ?>
        <div class="content">
            <?php \App\Core\View::include('partials.flash-messages'); ?>
            <?= $slot ?? $content ?? '' ?>
        </div>
        <?php \App\Core\View::include('partials.footer'); ?>
    </div>
</div>
<?php \App\Core\View::include('partials.modal'); ?>
<?php
$slot = ob_get_clean();
require __DIR__ . '/base.php';
