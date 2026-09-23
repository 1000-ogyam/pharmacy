<?php
$title = $title ?? config('app.name');
$slot = $slot ?? $content ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · <?= e(config('app.name')) ?></title>
    <meta name="theme-color" content="#1565c0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="PL Pharma">
    <link rel="icon" href="<?= e(asset('img/pl-logo.png')) ?>" type="image/png">
    <link rel="apple-touch-icon" href="<?= e(asset('img/pwa/apple-touch-180.png')) ?>">
    <link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=17">
    <script>
        try {
            if (localStorage.getItem('pharmacore.sidebar') === '1') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    </script>
</head>
<body>
    <?= $slot ?>
    <?php \App\Core\View::include('partials.install-sheet'); ?>
    <script src="<?= e(asset('js/app.js')) ?>?v=4"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(<?= json_encode(url('/sw.js')) ?>, { scope: <?= json_encode(url('/')) ?> }).catch(function () {});
        }
    </script>
</body>
</html>
