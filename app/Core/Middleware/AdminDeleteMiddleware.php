<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class AdminDeleteMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        if (!$request->isMethod('DELETE')) {
            return;
        }

        if (auth()->hasRole('admin')) {
            return;
        }

        if ($request->wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Only administrators can delete records.']);
            exit;
        }

        abort(403, 'Only administrators can delete records.');
    }
}
