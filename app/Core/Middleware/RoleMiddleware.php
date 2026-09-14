<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class RoleMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        $roles = array_filter(array_map('trim', explode(',', (string) $param)));

        if ($roles === [] || auth()->hasRole(...$roles)) {
            return;
        }

        if ($request->wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Forbidden.']);
            exit;
        }

        abort(403, 'You do not have permission to access this page.');
    }
}
