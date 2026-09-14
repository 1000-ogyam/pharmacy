<div class="auth-card">
    <h1><?= e((string) ($code ?? 500)) ?></h1>
    <p class="lead"><?= e($message ?: 'Something went wrong.') ?></p>
    <a class="btn" href="<?= e(url('/dashboard')) ?>">Back to dashboard</a>
</div>
