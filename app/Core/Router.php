<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

final class Router
{
    /** @var list<array{method:string,path:string,handler:mixed,middleware:list<string>}> */
    private array $routes = [];

    /** @var list<string> */
    private array $groupMiddleware = [];

    private string $groupPrefix = '';

    /** @var list<int> */
    private array $lastBatch = [];

    public function get(string $path, mixed $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): self
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): self
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): self
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): self
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function resource(string $path, string $controller): self
    {
        $start = count($this->routes);
        $path = '/' . trim($path, '/');
        $this->get($path, [$controller, 'index']);
        $this->get($path . '/create', [$controller, 'create']);
        $this->post($path, [$controller, 'store']);
        $this->get($path . '/{id}', [$controller, 'show']);
        $this->get($path . '/{id}/edit', [$controller, 'edit']);
        $this->put($path . '/{id}', [$controller, 'update']);
        $this->patch($path . '/{id}', [$controller, 'update']);
        $this->delete($path . '/{id}', [$controller, 'destroy']);
        $this->lastBatch = range($start, count($this->routes) - 1);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        $list = is_array($middleware) ? $middleware : [$middleware];

        foreach ($this->lastBatch as $index) {
            if (!isset($this->routes[$index])) {
                continue;
            }
            $this->routes[$index]['middleware'] = array_values(array_unique([
                ...$this->routes[$index]['middleware'],
                ...$list,
            ]));
        }

        return $this;
    }

    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = $previousPrefix . '/' . trim((string) ($options['prefix'] ?? ''), '/');
        $this->groupPrefix = '/' . trim($this->groupPrefix, '/');
        if ($this->groupPrefix === '/') {
            $this->groupPrefix = '';
        }

        $groupMw = $options['middleware'] ?? [];
        $this->groupMiddleware = array_values(array_unique([
            ...$previousMiddleware,
            ...(is_array($groupMw) ? $groupMw : [$groupMw]),
        ]));

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] !== $method || !$this->matches($route['path'], $path, $params)) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware, $request);
            }

            $this->invoke($route['handler'], $request, $params);
            return;
        }

        abort(404, 'The page you requested was not found.');
    }

    private function add(string $method, string $path, mixed $handler): self
    {
        $full = $this->groupPrefix . '/' . ltrim($path, '/');
        $full = '/' . trim($full, '/');
        if ($full === '') {
            $full = '/';
        }

        $this->routes[] = [
            'method' => $method,
            'path' => $full,
            'handler' => $handler,
            'middleware' => $this->groupMiddleware,
        ];
        $this->lastBatch = [array_key_last($this->routes)];

        return $this;
    }

    private function matches(string $pattern, string $path, array &$params): bool
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return false;
        }

        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return true;
    }

    private function runMiddleware(string $definition, Request $request): void
    {
        $name = $definition;
        $param = null;

        if (str_contains($definition, ':')) {
            [$name, $param] = explode(':', $definition, 2);
        }

        $class = match ($name) {
            'auth' => Middleware\AuthMiddleware::class,
            'role' => Middleware\RoleMiddleware::class,
            'csrf' => Middleware\CsrfMiddleware::class,
            'guest' => Middleware\GuestMiddleware::class,
            default => throw new RuntimeException('Unknown middleware: ' . $name),
        };

        (new $class())->handle($request, $param);
    }

    private function invoke(mixed $handler, Request $request, array $params): void
    {
        if ($handler instanceof Closure) {
            $handler($request, $params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $args = [];

            foreach ($params as $value) {
                $args[] = ctype_digit((string) $value) ? (int) $value : $value;
            }

            $controller->{$method}($request, ...$args);
            return;
        }

        throw new RuntimeException('Invalid route handler.');
    }
}
