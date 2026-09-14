<div class="auth-card">
    <h1>403</h1>
    <p class="lead"><?= e($message ?: 'You do not have access to this page.') ?></p>
    <a class="btn" href="<?= e(url('/dashboard')) ?>">Back to dashboard</a>
</div>
