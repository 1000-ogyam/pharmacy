<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;

require dirname(__DIR__) . '/app/bootstrap.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('#/public(/|$)#', $requestUri)) {
    $clean = preg_replace('#/public#', '', $requestUri, 1) ?: '/';
    $query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
        ? '?' . $_SERVER['QUERY_STRING']
        : '';
    header('Location: ' . $clean . $query, true, 301);
    exit;
}

try {
    $router = new Router();
    require dirname(__DIR__) . '/routes/web.php';
    $router->dispatch(Request::capture());
} catch (Throwable $e) {
    $debug = (bool) config('app.debug', false);
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    $setup = database_setup_message($e);
    if ($setup !== null) {
        try {
            abort(500, $setup);
        } catch (Throwable) {
            echo '<h1>Database error</h1><p>' . htmlspecialchars($setup, ENT_QUOTES) . '</p>';
            exit;
        }
    }

    if ($debug) {
        echo '<h1>Server error</h1><pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine() . "\n\n" . $e->getTraceAsString(), ENT_QUOTES) . '</pre>';
        exit;
    }

    try {
        abort(500, 'Something went wrong.');
    } catch (Throwable) {
        echo '<h1>Server error</h1><p>Something went wrong.</p>';
        exit;
    }
}
