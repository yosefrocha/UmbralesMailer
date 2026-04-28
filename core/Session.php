<?php

declare(strict_types=1);

final class Session
{
    private const INACTIVITY_TIMEOUT = 7200; // 2 horas en segundos

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function checkExpiry(): bool
    {
        $lastActivity = (int) ($_SESSION['_last_activity'] ?? 0);

        if ($lastActivity === 0) {
            $_SESSION['_last_activity'] = time();
            return true;
        }

        $elapsed = time() - $lastActivity;

        if ($elapsed > self::INACTIVITY_TIMEOUT) {
            $_SESSION = [];
            return false;
        }

        $_SESSION['_last_activity'] = time();
        return true;
    }

    public static function secondsUntilExpiry(): int
    {
        $lastActivity = (int) ($_SESSION['_last_activity'] ?? time());
        $elapsed = time() - $lastActivity;
        return max(0, self::INACTIVITY_TIMEOUT - $elapsed);
    }
}
