<?php

declare(strict_types=1);

namespace App\Services\Sms;

use GuzzleHttp\Client;

final class HubtelSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        $apiKey = (string) config('sms.api_key', '');

        if ($apiKey === '') {
            return ['ok' => true, 'simulated' => true, 'provider' => 'hubtel', 'message_id' => 'local'];
        }

        $client = new Client(['timeout' => 15]);
        $response = $client->post('https://sms.hubtel.com/v1/messages/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ],
            'json' => [
                'from' => config('sms.sender_id'),
                'to' => $to,
                'content' => $message,
            ],
        ]);

        $body = json_decode((string) $response->getBody(), true) ?? [];
        return ['ok' => true, 'provider' => 'hubtel', 'response' => $body];
    }
}
