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
            if ($this->expectsJson()) {
                $this->json(['ok' => false, 'error' => 'Recurso no válido.'], 404);
            }
            http_response_code(404);
            exit('Recurso no válido.');
        }

        return $id;
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo $json === false ? '{"ok":false,"error":"Error al generar respuesta JSON."}' : $json;
        exit;
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['_token'] ?? '';

        if (!Csrf::validate(is_string($token) ? $token : null)) {
            if ($this->expectsJson()) {
                $this->json(['ok' => false, 'error' => 'Token CSRF inválido. Recarga la página e intenta de nuevo.'], 419);
            }
            http_response_code(419);
            exit('Token CSRF inválido.');
        }
    }

    private function expectsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return str_contains($accept, 'application/json') || strtolower($requestedWith) === 'xmlhttprequest';
    }
}
