<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;

final class GuestMiddleware
{
    public function handle(Request $request, ?string $param = null): void
    {
        if (auth()->check()) {
            redirect('/dashboard');
        }
    }
}
