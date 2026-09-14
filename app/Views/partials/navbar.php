<?php $user = $authUser ?? auth()->user(); ?>
<header class="navbar no-print">
    <div class="navbar-left">
        <button type="button" class="icon-btn nav-burger" data-sidebar-open aria-label="Open menu">
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
    </div>
    <div class="navbar-meta">
        <span class="branch-chip"><?= e($user->branch_name ?? 'Branch') ?></span>
        <div class="user-chip">
            <span class="avatar"><?= e(strtoupper(substr((string) ($user->name ?? 'U'), 0, 1))) ?></span>
            <span class="user-name"><?= e($user->name ?? '') ?></span>
        </div>
        <a class="btn btn-outline btn-sm" href="<?= e(url('/help')) ?>">Help</a>
        <form method="post" action="<?= e(url('/logout')) ?>" class="signout-form">
            <?= csrf_field() ?>
            <button class="btn btn-outline btn-sm" type="submit">Sign out</button>
        </form>
    </div>
</header>
