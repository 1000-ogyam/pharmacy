<?php
ob_start();
?>
<div class="help-public">
    <header class="help-public-bar">
        <a class="help-public-brand" href="<?= e(url('/login')) ?>">
            <img src="<?= e(asset('img/pl-logo.png')) ?>" alt="PL Pharmaceuticals Ltd">
            <span>PL PharmaCore</span>
        </a>
        <div class="help-public-actions">
            <?php \App\Core\View::include('partials.install-app'); ?>
            <a class="btn btn-sm" href="<?= e(url('/login')) ?>">Sign in</a>
        </div>
    </header>
    <div class="help-public-body">
        <?= $slot ?? $content ?? '' ?>
    </div>
    <?php \App\Core\View::include('partials.footer'); ?>
</div>
<?php
$slot = ob_get_clean();
require __DIR__ . '/base.php';
