<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * @return array{ok: bool, provider: string, simulated?: bool, response?: mixed, error?: string, message_id?: string}
     */
    public function send(string $to, string $message): array;
}
