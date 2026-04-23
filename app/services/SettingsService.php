<?php

declare(strict_types=1);

final class SettingsService
{
    public function all(): array
    {
        $settingModel = new Setting();
        $rows = $settingModel->allIndexed();
        $defaults = require CONFIG_PATH . '/ses.php';
        $mapped = [
            'ses_region' => $defaults['region'] ?? '',
            'ses_key' => $defaults['key'] ?? '',
            'ses_secret' => $defaults['secret'] ?? '',
            'ses_from_email' => $defaults['from_email'] ?? '',
            'ses_from_name' => $defaults['from_name'] ?? '',
            'ses_reply_to' => $defaults['reply_to'] ?? '',
            'ses_configuration_set' => $defaults['configuration_set'] ?? '',
        ];

        foreach ($rows as $key => $row) {
            $mapped[$key] = (int) $row['is_encrypted'] === 1 ? Crypto::decrypt((string) $row['setting_value']) : $row['setting_value'];
        }
        return $mapped;
    }

    public function save(array $data): void
    {
        $settingModel = new Setting();
        $sensitive = ['ses_key', 'ses_secret'];
        foreach ($data as $key => $value) {
            $settingModel->set($key, $value !== '' ? (string) $value : null, in_array($key, $sensitive, true));
        }
    }
}
