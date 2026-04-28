<?php

declare(strict_types=1);

final class RateLimiter
{
    private const TABLE = 'login_attempts';

    /**
     * Registra un intento de login fallido
     */
    public static function recordFailedLogin(string $email, string $ip): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'INSERT INTO login_attempts (email, ip_address, attempted_at)
                 VALUES (:email, :ip, NOW())'
            );
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        } catch (Throwable) {
            // Silencioso si la tabla no existe aún
        }
    }

    /**
     * Verifica si una IP o email está bloqueado
     */
    public static function isBlocked(string $email, string $ip): bool
    {
        try {
            $db = Database::connection();

            // Bloqueo por IP: más de 10 intentos en 15 minutos
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS total FROM login_attempts
                 WHERE ip_address = :ip
                   AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );
            $stmt->execute(['ip' => $ip]);
            $row = $stmt->fetch();
            if ((int) ($row['total'] ?? 0) >= 10) {
                return true;
            }

            // Bloqueo por email: más de 5 intentos en 10 minutos
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS total FROM login_attempts
                 WHERE email = :email
                   AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)'
            );
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            if ((int) ($row['total'] ?? 0) >= 5) {
                return true;
            }

            return false;
        } catch (Throwable) {
            return false; // Si hay error, no bloquear
        }
    }

    /**
     * Limpia intentos antiguos (llamar en login exitoso o por cron)
     */
    public static function clearAttempts(string $email, string $ip): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'DELETE FROM login_attempts WHERE email = :email OR ip_address = :ip'
            );
            $stmt->execute(['email' => $email, 'ip' => $ip]);
        } catch (Throwable) {
        }
    }

    /**
     * Retorna cuántos intentos quedan antes del bloqueo por email
     */
    public static function remainingAttempts(string $email): int
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS total FROM login_attempts
                 WHERE email = :email
                   AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)'
            );
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            return max(0, 5 - (int) ($row['total'] ?? 0));
        } catch (Throwable) {
            return 5;
        }
    }

    /**
     * Obtiene la IP real del visitante
     */
    public static function getIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
