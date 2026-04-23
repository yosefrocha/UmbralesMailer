<?php

declare(strict_types=1);

final class Setting extends Model
{
    public function allIndexed(): array
    {
        $rows = $this->fetchAll('SELECT * FROM settings');
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row;
        }
        return $result;
    }

    public function set(string $key, ?string $value, bool $encrypted = false): void
    {
        $storedValue = $encrypted && $value !== null ? Crypto::encrypt($value) : $value;
        $sql = 'INSERT INTO settings (setting_key, setting_value, is_encrypted)
                VALUES (:setting_key, :setting_value, :is_encrypted)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), is_encrypted = VALUES(is_encrypted)';
        $this->execute($sql, [
            'setting_key' => $key,
            'setting_value' => $storedValue,
            'is_encrypted' => $encrypted ? 1 : 0,
        ]);
    }
}
