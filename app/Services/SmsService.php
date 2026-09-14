<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Sms\QueuedSmsService;
use App\Services\Sms\SmsService as SmsServiceContract;

final class SmsService
{
    public static function make(): SmsServiceContract
    {
        return new QueuedSmsService();
    }
}
