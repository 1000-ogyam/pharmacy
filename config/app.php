<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'PL Pharma'),
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'base_path' => env('APP_BASE_PATH', '/'),
    'timezone' => 'Africa/Accra',
    'currency' => 'GHS',
    'locale' => 'en_GH',
    'session_name' => 'plpharmacore_session',
    'login_max_attempts' => 5,
    'login_lock_minutes' => 15,
    'per_page' => 10,
    'ussd_webhook_secret' => env('USSD_WEBHOOK_SECRET', ''),
];
