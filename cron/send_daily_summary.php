<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\Sms\QueuedSmsService;

require dirname(__DIR__) . '/app/bootstrap.php';

$row = Database::instance()->fetch(
    'SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS total
     FROM sales WHERE deleted_at IS NULL AND DATE(created_at) = CURDATE()'
);

$message = sprintf(
    'PL Pharma daily summary: %d sales totalling %s.',
    (int) ($row['cnt'] ?? 0),
    money($row['total'] ?? 0)
);

(new QueuedSmsService())->send('0244000000', $message, 'legal_notice');
echo $message . PHP_EOL;
