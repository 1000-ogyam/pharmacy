<?php
$href = $href ?? '/';
$label = $label ?? '';
$active = is_active_path($href);

$icons = [
    '/dashboard' => 'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z',
    '/pos' => 'M6 7h12l-1 11H7L6 7zm3-3h6l1 3H8l1-3z',
    '/wholesale' => 'M4 10h16v9H4zm2-5h12l2 5H4z',
    '/customers' => 'M12 12a3.5 3.5 0 1 0-3.5-3.5A3.5 3.5 0 0 0 12 12zm-7 8a7 7 0 0 1 14 0',
    '/credit' => 'M3 8h18v10H3zm0-2h18v2H3z',
    '/products' => 'M8 4h8l2 4H6zm-2 4h16v12H6z',
    '/inventory' => 'M4 7l8-3 8 3-8 3zm0 0v10l8 3 8-3V7',
    '/inventory/batches' => 'M4 7h16M4 12h16M4 17h16',
    '/purchase-orders' => 'M7 4h10v16H7zm3 4h4M10 12h4M10 16h3',
    '/suppliers' => 'M3 17h18M5 17V9h8v8m3-5h3l2 3v2h-5z',
    '/prescriptions' => 'M8 3h8v18H8zm3 5h2m-1 0v6',
    '/nhis' => 'M12 3l8 4v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V7z',
    '/deliveries' => 'M3 16V8h11v8H3zm11-4h4l3 3v1h-7zM7 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm10 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
    '/returns' => 'M7 7H3v4m0-1a8 8 0 1 0 2.2-5.6',
    '/approvals' => 'M5 12l5 5L20 7',
    '/staff' => 'M12 12a3.2 3.2 0 1 0-3.2-3.2A3.2 3.2 0 0 0 12 12zM6 20a6 6 0 0 1 12 0M18 8h3M19.5 6.5v3',
    '/accounting' => 'M6 4h12v16H6zm3 4h6M9 12h6M9 16h4',
    '/reports' => 'M5 19V9m7 10V5m7 14v-7',
    '/sms' => 'M4 6h16v10H8l-4 3z',
    '/portal' => 'M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z',
    '/portal/orders' => 'M6 7h12l-1 11H7L6 7z',
    '/portal/invoices' => 'M7 4h10v16H7zm3 4h4M10 12h4',
    '/supplier-portal' => 'M4 7l8-3 8 3-8 3zm0 0v10l8 3 8-3V7',
];
$path = $icons[$href] ?? 'M5 12h14';
?>
<a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= e(url($href)) ?>" title="<?= e($label) ?>">
    <svg class="nav-ico" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
        <path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="<?= e($path) ?>"/>
    </svg>
    <span class="nav-text"><?= e($label) ?></span>
</a>
