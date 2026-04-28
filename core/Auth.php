<?php

declare(strict_types=1);

final class Auth
{
    public static function user(): ?array
    {
        return Session::get('auth_user');
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function login(array $user): void
    {
        Session::set('auth_user', [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ]);

        // Registrar inicio de actividad para expiración
        $_SESSION['_last_activity'] = time();

        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        Session::forget('auth_user');
        Session::forget('force_password_change');
        Session::forget('2fa_pending');

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        session_destroy();
    }

    public static function requireAuth(): bool
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }

        // Verificar expiración de sesión
        if (!Session::checkExpiry()) {
            header('Location: /login?expired=1');
            exit;
        }

        return true;
    }

    public static function requireGuest(): bool
    {
        if (self::check()) {
            header('Location: /');
            exit;
        }

        return true;
    }

    public static function requireAdmin(): bool
    {
        if (!self::requireAuth()) {
            return false;
        }

        if (!self::isAdmin()) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Prohibido']);
            return false;
        }

        return true;
    }
}
