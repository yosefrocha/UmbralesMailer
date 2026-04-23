<?php

declare(strict_types=1);

final class Crypto
{
    public static function encrypt(string $plainText): string
    {
        $key = self::key();
        if ($key === '') {
            return $plainText;
        }

        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(string $cipherText): string
    {
        $key = self::key();
        if ($key === '') {
            return $cipherText;
        }

        $decoded = base64_decode($cipherText, true);
        if ($decoded === false || strlen($decoded) < 17) {
            return $cipherText;
        }

        $iv = substr($decoded, 0, 16);
        $cipherRaw = substr($decoded, 16);
        $plain = openssl_decrypt($cipherRaw, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? '' : $plain;
    }

    private static function key(): string
    {
        $config = require CONFIG_PATH . '/app.php';
        $raw = (string) ($config['encryption_key'] ?? '');
        return $raw === '' ? '' : hash('sha256', $raw, true);
    }
}
