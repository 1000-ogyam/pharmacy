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

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';
$router->dispatch(Request::capture());
