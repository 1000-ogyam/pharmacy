<?php

declare(strict_types=1);

use App\Services\Sms\SmsQueueProcessor;

require dirname(__DIR__) . '/app/bootstrap.php';

$counts = SmsQueueProcessor::make()->process();
echo sprintf(
    "SMS queue: sent %d, retrying %d, failed %d\n",
    $counts['sent'],
    $counts['skipped'],
    $counts['failed']
);
