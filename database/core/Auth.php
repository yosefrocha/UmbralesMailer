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
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        Session::forget('auth_user');
        Session::forget('force_password_change');
        session_regenerate_id(true);
    }

    public static function requireAuth(): bool
    {
        if (!self::check()) {
            header('Location: /login');
            return false;
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
