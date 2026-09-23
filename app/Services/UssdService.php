<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class UssdService
{
    public function handle(string $sessionId, string $msisdn, string $input): string
    {
        $session = Database::instance()->fetch(
            'SELECT * FROM ussd_sessions WHERE session_id = :sid LIMIT 1',
            [':sid' => $sessionId]
        );

        if (!$session) {
            Database::instance()->execute(
                'INSERT INTO ussd_sessions (session_id, msisdn, current_menu, data_json, last_input, created_at, updated_at, expires_at)
                 VALUES (:sid, :msisdn, :menu, :data, :input, :created, :updated, :expires)',
                [
                    ':sid' => $sessionId,
                    ':msisdn' => $msisdn,
                    ':menu' => 'home',
                    ':data' => '{}',
                    ':input' => $input,
                    ':created' => date('Y-m-d H:i:s'),
                    ':updated' => date('Y-m-d H:i:s'),
                    ':expires' => date('Y-m-d H:i:s', time() + 180),
                ]
            );

            return "CON PL Pharma\n1. Check order status\n2. Reorder last items\n3. Opt out of SMS";
        }

        $menu = (string) $session['current_menu'];
        $response = $this->next($menu, $input);

        Database::instance()->execute(
            'UPDATE ussd_sessions SET current_menu = :menu, last_input = :input, updated_at = :updated WHERE session_id = :sid',
            [
                ':menu' => $response['menu'],
                ':input' => $input,
                ':updated' => date('Y-m-d H:i:s'),
                ':sid' => $sessionId,
            ]
        );

        return $response['text'];
    }

    private function next(string $menu, string $input): array
    {
        if ($menu === 'home') {
            return match ($input) {
                '1' => ['menu' => 'order', 'text' => "CON Enter invoice number:"],
                '2' => ['menu' => 'end', 'text' => "END Your last order will be reviewed by a pharmacist. We will SMS you shortly."],
                '3' => ['menu' => 'end', 'text' => "END You have been opted out of marketing SMS."],
                default => ['menu' => 'home', 'text' => "CON Invalid option.\n1. Check order status\n2. Reorder last items\n3. Opt out of SMS"],
            };
        }

        if ($menu === 'order') {
            return ['menu' => 'end', 'text' => 'END Invoice ' . $input . ' is being looked up. You will receive an SMS update.'];
        }

        return ['menu' => 'end', 'text' => 'END Thank you for using PL Pharma.'];
    }
}
