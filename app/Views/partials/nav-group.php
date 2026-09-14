<?php
$id = $id ?? 'group';
$label = $label ?? '';
$icon = $icon ?? 'M5 12h14';
$links = $links ?? [];
$current = false;

foreach ($links as $link) {
    if (is_active_path($link['href'] ?? '')) {
        $current = true;
        break;
    }
}
?>
<div class="nav-group<?= $current ? ' is-open is-current' : '' ?>" data-nav-group="<?= e($id) ?>">
    <button type="button" class="nav-group-toggle" aria-expanded="<?= $current ? 'true' : 'false' ?>">
        <svg class="nav-ico" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
            <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="<?= e($icon) ?>"/>
        </svg>
        <span class="nav-text"><?= e($label) ?></span>
        <svg class="nav-caret" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true">
            <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M8 10l4 4 4-4"/>
        </svg>
    </button>
    <div class="nav-group-items">
        <div class="nav-group-inner">
            <?php foreach ($links as $link): ?>
                <?php \App\Core\View::include('partials.nav-link', $link); ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
