<?php

declare(strict_types=1);

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function intId(string $value): int
    {
        $id = (int) $value;

        if ($id <= 0) {
            http_response_code(404);
            exit('Recurso no válido.');
        }

        return $id;
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['_token'] ?? '';

        if (!Csrf::validate(is_string($token) ? $token : null)) {
            http_response_code(419);
            exit('Token CSRF inválido.');
        }
    }
}