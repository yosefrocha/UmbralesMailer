<?php

declare(strict_types=1);

final class Sanitizer
{
    // Caracteres especiales no permitidos en nombres de personas
    private const INVALID_NAME_CHARS = '/[<>{}()\[\]\\\\\/\*\+\=\^\$\|@#%&!;:\"\'`~]/u';
    // Patrones de inyección SQL y XSS
    private const SQL_PATTERNS = [
        '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b|\bCREATE\b|\bALTER\b|\bEXEC\b|\bEXECUTE\b)/i',
        '/(-{2}|\/\*|\*\/|;)/u',
        '/(SLEEP\s*\(|BENCHMARK\s*\(|LOAD_FILE\s*\()/i',
    ];

    private const XSS_PATTERNS = [
        '/<script\b[^>]*>(.*?)<\/script>/is',
        '/javascript\s*:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
    ];

    /**
     * Limpia un string general — quita espacios extras y caracteres de control
     */
public static function clean(string $value): string
{
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? $value;
    return trim($value);
}

    /**
     * Sanitiza un nombre de persona — permite letras, espacios, guiones, apóstrofes y caracteres latinos
     */
    public static function name(string $value): string
{
    $value = self::clean($value);
    $value = preg_replace(self::INVALID_NAME_CHARS, '', $value) ?? $value;
    $value = preg_replace('/\s+/', ' ', $value) ?? $value;
    return trim($value);
}

    /**
     * Valida que un nombre sea válido — retorna true si es aceptable
     */
    public static function isValidName(string $value): bool
    {
        if ($value === '') {
            return true; // Nombres opcionales pueden estar vacíos
        }

        // Verificar longitud
        if (mb_strlen($value) > 100) {
            return false;
        }

        // Verificar que no tenga caracteres inválidos
        if (preg_match(self::INVALID_NAME_CHARS, $value)) {
            return false;
        }

        // Debe contener al menos una letra
        if (!preg_match('/\p{L}/u', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Valida y sanitiza un email
     */
    public static function email(string $value): string
    {
        $value = self::clean($value);
        return strtolower($value);
    }

    /**
     * Verifica si un email es válido
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && mb_strlen($email) <= 255;
    }

    /**
     * Detecta posibles intentos de inyección SQL
     */
    public static function hasSqlInjection(string $value): bool
    {
        foreach (self::SQL_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Detecta posibles intentos de XSS
     */
    public static function hasXss(string $value): bool
    {
        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si un valor es sospechoso (SQL injection o XSS)
     */
    public static function isSuspicious(string $value): bool
    {
        return self::hasSqlInjection($value) || self::hasXss($value);
    }

    /**
     * Sanitiza texto para output HTML seguro
     */
    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitiza un número entero
     */
    public static function int(mixed $value, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $int = (int) $value;
        return max($min, min($max, $int));
    }

    /**
     * Valida que un string esté dentro de una lista de valores permitidos
     */
    public static function inList(string $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    /**
     * Sanitiza texto para uso en CSV (evita fórmulas maliciosas)
     */
    public static function csv(string $value): string
    {
        $value = self::clean($value);
        // Prevenir inyección de fórmulas en Excel/CSV
        if (in_array(mb_substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)) {
            $value = "'" . $value;
        }
        return $value;
    }
}
