<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class CsrfMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = (string) $request->input('_token', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $sessionToken = (string) csrf_token();

        if ($token === '' || !hash_equals($sessionToken, $token)) {
            if ($request->wantsJson()) {
                http_response_code(419);
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => 'CSRF token mismatch.']);
                exit;
            }

            abort(419, 'Your session expired. Please refresh and try again.');
        }
    }
}
