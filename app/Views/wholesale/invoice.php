<?php
/**
 * @var \App\Models\WholesaleOrder $order
 * @var array<string, mixed> $invoiceRow
 * @var \App\Models\Customer|null $customer
 * @var \App\Models\Branch|null $branch
 * @var list<array<string, mixed>> $items
 * @var bool $pdf
 * @var bool $standalone
 * @var bool $autoPrint
 */
$pdf = !empty($pdf);
$standalone = !empty($standalone);
$autoPrint = !empty($autoPrint);
$invoiceNumber = (string) $invoiceRow['invoice_number'];
$pdfUrl = url('/wholesale/orders/' . $order->id . '/invoice/pdf');
$screenUrl = url('/wholesale/orders/' . $order->id . '/invoice');
?>
<?php if ($pdf): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order <?= e($order->order_number) ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; margin: 24px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 6px 8px; text-align: left; }
        th { font-size: 10px; text-transform: uppercase; color: #64748b; }
        .totals { margin-top: 14px; width: 260px; margin-left: auto; }
        .totals div { display: flex; justify-content: space-between; padding: 3px 0; }
        .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #0f172a; margin-top: 6px; padding-top: 8px; }
        .invoice-doc-head { display: table; width: 100%; }
        .invoice-doc-head > div { display: table-cell; width: 50%; vertical-align: top; }
        .invoice-doc-grid { display: table; width: 100%; margin-top: 16px; }
        .invoice-doc-grid > div { display: table-cell; width: 50%; vertical-align: top; }
    </style>
</head>
<body>
<?php \App\Core\View::include('wholesale.partials.order-document', get_defined_vars()); ?>
</body>
</html>
<?php elseif ($standalone): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order <?= e($order->order_number) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/print-order.css')) ?>?v=1">
</head>
<body>
    <div class="order-print-toolbar">
        <span class="toolbar-title"><?= e($order->order_number) ?> · <?= e($invoiceNumber) ?></span>
        <button type="button" class="btn-primary" onclick="window.print()">Print order</button>
        <a href="<?= e($pdfUrl) ?>">Download PDF</a>
        <a href="<?= e(url('/wholesale')) ?>">Wholesale</a>
    </div>
    <main class="order-document">
        <?php \App\Core\View::include('wholesale.partials.order-document', get_defined_vars()); ?>
    </main>
    <?php if ($autoPrint): ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <?php endif; ?>
</body>
</html>
<?php endif; ?>
