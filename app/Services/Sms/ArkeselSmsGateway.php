<?php

declare(strict_types=1);

namespace App\Services\Sms;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

final class ArkeselSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): array
    {
        $apiKey = (string) config('sms.api_key', '');
        $recipient = $this->normalizeMsisdn($to);

        if ($apiKey === '') {
            return [
                'ok' => true,
                'simulated' => true,
                'provider' => 'arkesel',
                'message_id' => 'local',
                'response' => ['status' => 'simulated'],
            ];
        }

        if ($recipient === '') {
            return ['ok' => false, 'provider' => 'arkesel', 'error' => 'Invalid destination number.'];
        }

        $payload = [
            'sender' => (string) config('sms.sender_id', 'PLPharma'),
            'message' => $message,
            'recipients' => [$recipient],
        ];

        if (filter_var(config('sms.sandbox', false), FILTER_VALIDATE_BOOLEAN)) {
            $payload['sandbox'] = true;
        }

        try {
            $client = new Client(['timeout' => 20]);
            $response = $client->post((string) config('sms.arkesel_url', 'https://sms.arkesel.com/api/v2/sms/send'), [
                'http_errors' => false,
                'headers' => [
                    'api-key' => $apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Arkesel request failed: ' . $e->getMessage(), 0, $e);
        }

        $body = json_decode((string) $response->getBody(), true);
        $body = is_array($body) ? $body : [];
        $httpOk = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        $status = strtolower((string) ($body['status'] ?? ''));
        $ok = $httpOk && ($status === '' || $status === 'success');

        if (!$ok) {
            $error = (string) ($body['message'] ?? $body['error'] ?? ('HTTP ' . $response->getStatusCode()));
            return [
                'ok' => false,
                'provider' => 'arkesel',
                'error' => $error,
                'response' => $body,
            ];
        }

        return [
            'ok' => true,
            'provider' => 'arkesel',
            'message_id' => $this->messageId($body),
            'response' => $body,
        ];
    }

    public function normalizeMsisdn(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '233') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '233' . substr($digits, 1);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '2')) {
            return '233' . $digits;
        }

        return $digits;
    }

    /** @param array<string, mixed> $body */
    private function messageId(array $body): string
    {
        $data = $body['data'] ?? null;
        if (is_array($data) && isset($data[0]) && is_array($data[0]) && isset($data[0]['id'])) {
            return (string) $data[0]['id'];
        }
        if (is_array($data) && isset($data['id'])) {
            return (string) $data['id'];
        }

        return (string) ($body['id'] ?? '');
    }
}
