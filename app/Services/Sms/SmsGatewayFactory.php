<?php

declare(strict_types=1);

namespace App\Services\Sms;

final class SmsGatewayFactory
{
    public static function make(): SmsGateway
    {
        $name = strtolower((string) config('sms.gateway', 'arkesel'));

        return match ($name) {
            'hubtel' => new HubtelSmsGateway(),
            'mnotify' => new MNotifySmsGateway(),
            default => new ArkeselSmsGateway(),
        };
    }
}
