<?php

declare(strict_types=1);

namespace App\Services\Sms;

use GuzzleHttp\Client;

final class MNotifySmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        $apiKey = (string) config('sms.api_key', '');

        if ($apiKey === '') {
            return ['ok' => true, 'simulated' => true, 'provider' => 'mnotify', 'message_id' => 'local'];
        }

        $client = new Client(['timeout' => 15]);
        $response = $client->post('https://api.mnotify.com/api/sms/quick', [
            'query' => ['key' => $apiKey],
            'json' => [
                'recipient' => [$to],
                'sender' => config('sms.sender_id'),
                'message' => $message,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true) ?? [];
        return ['ok' => true, 'provider' => 'mnotify', 'response' => $body];
    }
}
