<?php

declare(strict_types=1);

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
            $_SESSION['_csrf'] = self::newToken();
        }

        return $_SESSION['_csrf'];
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $current = $_SESSION['_csrf'] ?? null;
        if (is_string($current) && $current !== '' && hash_equals($current, $token)) {
            return true;
        }

        // Soporta temporalmente el token anterior para evitar falsos negativos
        // cuando el usuario tiene dos pestañas abiertas o recarga durante cambios de archivos.
        $previous = $_SESSION['_csrf_previous'] ?? null;
        return is_string($previous) && $previous !== '' && hash_equals($previous, $token);
    }

    public static function regenerate(): string
    {
        if (isset($_SESSION['_csrf']) && is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf_previous'] = $_SESSION['_csrf'];
        }

        $_SESSION['_csrf'] = self::newToken();
        return $_SESSION['_csrf'];
    }

    private static function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
