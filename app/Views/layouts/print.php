<?php
ob_start();
?>
<div class="print-page-shell">
    <?= $slot ?? $content ?? '' ?>
</div>
<?php
$slot = ob_get_clean();
require __DIR__ . '/base.php';
