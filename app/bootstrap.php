<?php

declare(strict_types=1);

use App\Core\Session;
use Dotenv\Dotenv;

$root = dirname(__DIR__);

if (PHP_VERSION_ID < 80200) {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'PL Pharma needs PHP 8.2 or newer. This server is running ' . PHP_VERSION . '. In Hostinger hPanel, set the domain PHP version to 8.2 or 8.3.';
    exit(1);
}

$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Missing vendor/autoload.php.\nOn the server run:\n  composer install --no-dev --optimize-autoloader";
    exit(1);
}

require $autoload;

if (is_file($root . '/.env')) {
    $dotenv = Dotenv::createImmutable($root);
    $dotenv->safeLoad();
}

date_default_timezone_set((string) config('app.timezone', 'Africa/Accra'));

if (filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

$logDir = $root . '/storage/logs';
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('log_errors', '1');
    ini_set('error_log', $logDir . '/php-error.log');
}

Session::start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
