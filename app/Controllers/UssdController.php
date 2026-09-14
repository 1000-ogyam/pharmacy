<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\UssdService;

final class UssdController extends Controller
{
    public function webhook(Request $request): never
    {
        $expected = (string) config('app.ussd_webhook_secret', '');
        $provided = (string) ($request->input('secret', $_SERVER['HTTP_X_USSD_SECRET'] ?? ''));

        if ($expected === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Forbidden';
            exit;
        }

        $sessionId = (string) $request->input('sessionId', $request->input('session_id', ''));
        $msisdn = (string) $request->input('msisdn', $request->input('phone', ''));
        $input = (string) $request->input('text', $request->input('message', ''));

        $response = (new UssdService())->handle($sessionId, $msisdn, $input);
        header('Content-Type: text/plain; charset=utf-8');
        echo $response;
        exit;
    }
}
