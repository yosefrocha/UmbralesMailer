<?php

declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method:string,pattern:string,action:array,middleware:array}> */
    private array $routes = [];

    public function get(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $action, $middleware);
    }

    public function post(string $pattern, array $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $action, $middleware);
    }

    private function add(string $method, string $pattern, array $action, array $middleware): void
    {
        $this->routes[] = compact('method', 'pattern', 'action', 'middleware');
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                if (is_array($middleware)) {
                    $instance = is_string($middleware[0]) ? null : $middleware[0];
                    $callable = [$instance ?: $middleware[0], $middleware[1]];
                } else {
                    $callable = $middleware;
                }

                if (is_callable($callable) && call_user_func($callable) === false) {
                    return;
                }
            }

            [$class, $action] = $route['action'];
            $controller = new $class();
            call_user_func_array([$controller, $action], array_values($params));
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'No encontrado']);
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}
