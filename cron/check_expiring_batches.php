<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\Sms\QueuedSmsService;

require dirname(__DIR__) . '/app/bootstrap.php';

$rows = Database::instance()->fetchAll(
    'SELECT b.*, p.name AS product_name
     FROM batches b
     JOIN products p ON p.id = b.product_id
     WHERE b.deleted_at IS NULL
       AND b.is_recalled = 0
       AND b.quantity_remaining > 0
       AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)'
);

$sms = new QueuedSmsService();
$to = '0244000000';

foreach ($rows as $row) {
    $sms->sendTemplate($to, 'expiry_alert', [
        'product_name' => $row['product_name'],
        'batch_number' => $row['batch_number'],
        'expiry_date' => $row['expiry_date'],
    ]);
    echo "queued expiry alert for {$row['batch_number']}\n";
}

echo 'Checked ' . count($rows) . " expiring batch(es).\n";
