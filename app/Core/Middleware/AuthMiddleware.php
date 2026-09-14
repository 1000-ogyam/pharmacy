<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class AuthMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        if (auth()->check()) {
            return;
        }

        if ($request->wantsJson()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Unauthenticated.']);
            exit;
        }

        flash('error', 'Please sign in to continue.');
        redirect('/');
    }
}
