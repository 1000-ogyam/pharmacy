<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsService
{
    public function send(string $to, string $message, ?string $templateKey = null, ?int $customerId = null): void;
}
