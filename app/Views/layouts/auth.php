<?php
ob_start();
?>
<div class="auth-wrap" data-auth-wrap>
    <canvas class="auth-field" data-auth-field aria-hidden="true"></canvas>
    <?= $slot ?? $content ?? '' ?>
    <?php \App\Core\View::include('partials.footer'); ?>
</div>
<?php
$slot = ob_get_clean();
require __DIR__ . '/base.php';
