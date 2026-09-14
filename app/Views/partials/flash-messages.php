<?php
$flashes = flashes();
foreach (['success', 'error', 'warning', 'info'] as $type):
    if (!empty($flashes[$type])):
?>
    <div class="flash flash-<?= e($type) ?>"><?= e($flashes[$type]) ?></div>
<?php
    endif;
endforeach;
