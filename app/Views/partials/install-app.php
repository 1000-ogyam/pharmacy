<?php
$installClass = $installClass ?? 'btn btn-outline btn-sm';
$installBlock = !empty($installBlock);
?>
<button type="button" class="<?= e($installClass) ?><?= $installBlock ? ' btn-block' : '' ?>" data-install-app>
    <span data-install-label>Click to install app</span>
</button>
