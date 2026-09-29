<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class ReadOnlyRoleMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        if (!auth()->hasRole('counter')) {
            return;
        }

        $method = strtoupper($request->method());
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            if ($request->wantsJson()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => 'Your role is read-only.']);
                exit;
            }

            abort(403, 'Your role is read-only. You cannot change data.');
        }

        $path = $request->path();
        if (preg_match('#/(create|edit)(/|$)#', $path) === 1) {
            abort(403, 'Your role is read-only. You cannot open edit forms.');
        }
    }
}
