<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function config(string $key, mixed $default = null): mixed
{
    static $cache = [];

    $parts = explode('.', $key);
    $file = array_shift($parts);

    if (!isset($cache[$file])) {
        $path = dirname(__DIR__, 2) . '/config/' . $file . '.php';
        $cache[$file] = is_file($path) ? require $path : [];
    }

    $value = $cache[$file];

    foreach ($parts as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }

    return $value;
}

function base_path(string $path = ''): string
{
    $root = dirname(__DIR__, 2);
    return $path === '' ? $root : $root . '/' . ltrim($path, '/');
}

function app_base_path(): string
{
    $configured = rtrim((string) env('APP_BASE_PATH', ''), '/');

    if ($configured === '' || $configured === '/') {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        return $script === '/' ? '' : rtrim($script, '/');
    }

    return $configured;
}

function url(string $path = ''): string
{
    $base = app_base_path();
    $path = '/' . ltrim($path, '/');

    if ($path === '/') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base . $path;
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path, int $code = 302): never
{
    $location = str_starts_with($path, 'http') ? $path : url($path);
    header('Location: ' . $location, true, $code);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('_old', []);
    return $old[$key] ?? $default;
}

function csrf_token(): string
{
    $token = Session::get('_csrf_token');

    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        Session::set('_csrf_token', $token);
    }

    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

function flash(string $key, mixed $value): void
{
    Session::flash($key, $value);
}

function flashes(?string $key = null): mixed
{
    if ($key === null) {
        return [
            'success' => Session::getFlash('success'),
            'error' => Session::getFlash('error'),
            'warning' => Session::getFlash('warning'),
            'info' => Session::getFlash('info'),
            'errors' => Session::getFlash('errors', []),
        ];
    }

    return Session::getFlash($key);
}

function errors(string $key): array
{
    $errors = Session::getFlash('errors', []);
    return $errors[$key] ?? [];
}

function has_error(string $key): bool
{
    return errors($key) !== [];
}

function auth(): Auth
{
    return Auth::instance();
}

function view(string $template, array $data = [], ?string $layout = null): string
{
    return View::render($template, $data, $layout);
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);

    if ($code === 404) {
        echo View::render('errors.404', ['message' => $message], 'auth');
    } elseif ($code === 403) {
        echo View::render('errors.403', ['message' => $message], 'auth');
    } else {
        echo View::render('errors.generic', ['code' => $code, 'message' => $message], 'auth');
    }

    exit;
}

function request_method(): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if ($method === 'POST' && isset($_POST['_method'])) {
        $spoofed = strtoupper((string) $_POST['_method']);
        if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $spoofed;
        }
    }

    return $method;
}

function per_page(): int
{
    return max(1, (int) config('app.per_page', 10));
}

function page_number(string $key = 'page'): int
{
    return max(1, (int) ($_GET[$key] ?? 1));
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = app_base_path();

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base)) ?: '/';
    }

    if (str_starts_with($uri, '/public/') || $uri === '/public') {
        $uri = substr($uri, strlen('/public')) ?: '/';
    }

    $uri = '/' . ltrim($uri, '/');

    if ($uri !== '/' && str_ends_with($uri, '/')) {
        $uri = rtrim($uri, '/');
    }

    return $uri;
}

function is_active_path(string $path): bool
{
    $current = request_path();
    $target = '/' . ltrim($path, '/');

    if ($target === '/') {
        return $current === '/dashboard' || $current === '/';
    }

    return $current === $target || str_starts_with($current, $target . '/');
}

function layout_for_role(?string $role = null): string
{
    $role ??= auth()->user()?->role_slug;

    return match ($role) {
        'cashier' => 'dashboard-retail',
        'pharmacist' => 'dashboard-pharmacist',
        'warehouse' => 'dashboard-warehouse',
        'finance' => 'dashboard-finance',
        'wholesale' => 'dashboard-wholesale',
        'customer' => 'portal-customer',
        'supplier' => 'portal-supplier',
        default => 'dashboard-admin',
    };
}
