<?php
$title = $title ?? config('app.name');
$slot = $slot ?? $content ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · PL PharmaCore</title>
    <link rel="icon" href="<?= e(asset('img/pl-logo.png')) ?>" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=14">
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
    <script src="<?= e(asset('js/app.js')) ?>?v=2"></script>
</body>
</html>
