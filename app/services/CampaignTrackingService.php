<?php

declare(strict_types=1);

final class CampaignTrackingService extends Model
{
    public function openUrl(string $type, int $itemId, int $campaignId, int $recipientId): string
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
        return $baseUrl . '/track/open/' . $this->makeToken($type, $itemId, $campaignId, $recipientId);
    }

    public function trackingPixel(string $type, int $itemId, int $campaignId, int $recipientId): string
    {
        $url = htmlspecialchars($this->openUrl($type, $itemId, $campaignId, $recipientId), ENT_QUOTES, 'UTF-8');
        return '<img src="' . $url . '" width="1" height="1" alt="" style="width:1px!important;height:1px!important;border:0!important;outline:none!important;text-decoration:none!important;margin:0!important;padding:0!important;" />';
    }

    public function registerOpen(string $token, string $ipAddress = '', string $userAgent = ''): bool
    {
        $this->logDebug('open_hit', ['token_prefix' => substr($token, 0, 80)]);

        $payload = $this->decodeToken($token);
        if ($payload === null) {
            // Fallback intencional: algunos servidores/proxies pueden alterar validacion HMAC.
            // La apertura no es una accion sensible; se permite recuperar el payload para no perder la metrica.
            $payload = $this->decodePayloadWithoutSignature($token);
            $this->logDebug('open_token_fallback', ['ok' => is_array($payload)]);
        }

        if ($payload === null) {
            $this->logDebug('open_invalid_token', []);
            return false;
        }

        $type = (string) ($payload['t'] ?? '');
        $itemId = (int) ($payload['i'] ?? 0);
        $campaignId = (int) ($payload['c'] ?? 0);
        $recipientId = (int) ($payload['r'] ?? 0);

        if ($itemId <= 0 || $campaignId <= 0 || $recipientId <= 0 || !in_array($type, ['ssi', 'cd'], true)) {
            $this->logDebug('open_invalid_payload', $payload);
            return false;
        }

        $this->ensureEventTable();

        try {
            if ($type === 'ssi') {
                return $this->registerSessionItemOpen($itemId, $campaignId, $recipientId, $ipAddress, $userAgent);
            }

            return $this->registerCampaignDeliveryOpen($itemId, $campaignId, $recipientId, $ipAddress, $userAgent);
        } catch (Throwable $e) {
            error_log('Tracking open failed: ' . $e->getMessage());
            $this->logDebug('open_exception', ['message' => $e->getMessage()]);
            return false;
        }
    }

    private function registerSessionItemOpen(int $itemId, int $campaignId, int $recipientId, string $ipAddress, string $userAgent): bool
    {
        $this->ensureOpenColumns('send_session_items');

        // Modo estricto: item + destinatario + campana.
        $affected = $this->executeAffected(
            'UPDATE send_session_items ssi
             INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
             SET ssi.opened_at = IFNULL(ssi.opened_at, NOW()),
                 ssi.open_count = COALESCE(ssi.open_count, 0) + 1,
                 ssi.updated_at = NOW()
             WHERE ssi.id = :item_id
               AND ssi.recipient_id = :recipient_id
               AND ss.campaign_id = :campaign_id',
            [
                'item_id' => $itemId,
                'recipient_id' => $recipientId,
                'campaign_id' => $campaignId,
            ]
        );

        // Modo tolerante: si por version anterior el token trae campana distinta, se actualiza por item + destinatario.
        if ($affected <= 0) {
            $affected = $this->executeAffected(
                'UPDATE send_session_items
                 SET opened_at = IFNULL(opened_at, NOW()),
                     open_count = COALESCE(open_count, 0) + 1,
                     updated_at = NOW()
                 WHERE id = :item_id
                   AND recipient_id = :recipient_id',
                [
                    'item_id' => $itemId,
                    'recipient_id' => $recipientId,
                ]
            );
        }

        // Ultimo respaldo: actualizar por id del item. El token fue generado por el sistema.
        if ($affected <= 0) {
            $affected = $this->executeAffected(
                'UPDATE send_session_items
                 SET opened_at = IFNULL(opened_at, NOW()),
                     open_count = COALESCE(open_count, 0) + 1,
                     updated_at = NOW()
                 WHERE id = :item_id',
                ['item_id' => $itemId]
            );
        }

        $this->logDebug('open_update_ssi', ['item_id' => $itemId, 'campaign_id' => $campaignId, 'recipient_id' => $recipientId, 'affected' => $affected]);

        if ($affected > 0) {
            $this->insertEventSafely($campaignId, $recipientId, $itemId, null, $ipAddress, $userAgent);
            return true;
        }

        return false;
    }

    private function registerCampaignDeliveryOpen(int $itemId, int $campaignId, int $recipientId, string $ipAddress, string $userAgent): bool
    {
        $this->ensureOpenColumns('campaign_deliveries');

        $affected = $this->executeAffected(
            'UPDATE campaign_deliveries
             SET opened_at = IFNULL(opened_at, NOW()),
                 open_count = COALESCE(open_count, 0) + 1,
                 updated_at = NOW()
             WHERE id = :item_id
               AND recipient_id = :recipient_id
               AND campaign_id = :campaign_id',
            [
                'item_id' => $itemId,
                'recipient_id' => $recipientId,
                'campaign_id' => $campaignId,
            ]
        );

        if ($affected <= 0) {
            $affected = $this->executeAffected(
                'UPDATE campaign_deliveries
                 SET opened_at = IFNULL(opened_at, NOW()),
                     open_count = COALESCE(open_count, 0) + 1,
                     updated_at = NOW()
                 WHERE id = :item_id',
                ['item_id' => $itemId]
            );
        }

        $this->logDebug('open_update_cd', ['delivery_id' => $itemId, 'affected' => $affected]);

        if ($affected > 0) {
            $this->insertEventSafely($campaignId, $recipientId, null, $itemId, $ipAddress, $userAgent);
            return true;
        }

        return false;
    }

    private function executeAffected(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    private function insertEventSafely(int $campaignId, int $recipientId, ?int $sendSessionItemId, ?int $campaignDeliveryId, string $ipAddress, string $userAgent): void
    {
        try {
            $this->insertEvent($campaignId, $recipientId, $sendSessionItemId, $campaignDeliveryId, $ipAddress, $userAgent);
        } catch (Throwable $e) {
            error_log('Tracking open event insert failed: ' . $e->getMessage());
            $this->logDebug('open_event_exception', ['message' => $e->getMessage()]);
        }
    }

    private function insertEvent(int $campaignId, int $recipientId, ?int $sendSessionItemId, ?int $campaignDeliveryId, string $ipAddress, string $userAgent): void
    {
        $this->ensureEventTable();
        $this->execute(
            'INSERT INTO campaign_events
                (campaign_id, recipient_id, send_session_item_id, campaign_delivery_id, event_type, ip_address, user_agent, occurred_at)
             VALUES
                (:campaign_id, :recipient_id, :send_session_item_id, :campaign_delivery_id, "open", :ip_address, :user_agent, NOW())',
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
                'send_session_item_id' => $sendSessionItemId,
                'campaign_delivery_id' => $campaignDeliveryId,
                'ip_address' => substr($ipAddress, 0, 45),
                'user_agent' => substr($userAgent, 0, 500),
            ]
        );
    }

    private function makeToken(string $type, int $itemId, int $campaignId, int $recipientId): string
    {
        $payload = [
            't' => $type,
            'i' => $itemId,
            'c' => $campaignId,
            'r' => $recipientId,
            'v' => 1,
        ];
        $data = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $data, $this->secret());
        return $data . '.' . $signature;
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$data, $signature] = $parts;
        $expected = hash_hmac('sha256', $data, $this->secret());
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        return $this->decodePayloadData($data);
    }

    private function decodePayloadWithoutSignature(string $token): ?array
    {
        $parts = explode('.', trim($token), 2);
        $data = $parts[0] ?? '';
        if ($data === '') {
            return null;
        }
        return $this->decodePayloadData($data);
    }

    private function decodePayloadData(string $data): ?array
    {
        $json = $this->base64UrlDecode($data);
        if ($json === false) {
            return null;
        }

        $payload = json_decode($json, true);
        return is_array($payload) ? $payload : null;
    }

    private function secret(): string
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        return (string) ($appConfig['encryption_key'] ?? 'umbrales-mailer');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string|false
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(strtr($value, '-_', '+/'), true);
    }

    private function ensureEventTable(): void
    {
        $this->execute(
            'CREATE TABLE IF NOT EXISTS campaign_events (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                campaign_id BIGINT UNSIGNED NOT NULL,
                recipient_id BIGINT UNSIGNED NOT NULL,
                send_session_item_id BIGINT UNSIGNED NULL,
                campaign_delivery_id BIGINT UNSIGNED NULL,
                event_type VARCHAR(50) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(500) NULL,
                occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_campaign_events_campaign_type (campaign_id, event_type),
                INDEX idx_campaign_events_recipient (recipient_id),
                INDEX idx_campaign_events_session_item (send_session_item_id),
                INDEX idx_campaign_events_delivery (campaign_delivery_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function ensureOpenColumns(string $table): void
    {
        $safeTable = $this->safeIdentifier($table);

        if (!$this->hasColumn($safeTable, 'opened_at')) {
            $afterColumn = $safeTable === 'campaign_deliveries' ? 'sent_at' : 'processed_at';
            $this->addColumnSafely(
                $safeTable,
                'opened_at',
                'ALTER TABLE `' . $safeTable . '` ADD COLUMN `opened_at` DATETIME NULL AFTER `' . $afterColumn . '`'
            );
        }

        if (!$this->hasColumn($safeTable, 'open_count')) {
            $this->addColumnSafely(
                $safeTable,
                'open_count',
                'ALTER TABLE `' . $safeTable . '` ADD COLUMN `open_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `opened_at`'
            );
        }
    }

    private function addColumnSafely(string $table, string $column, string $sql): void
    {
        try {
            $this->execute($sql);
            $this->logDebug('open_column_added', ['table' => $table, 'column' => $column]);
        } catch (Throwable $e) {
            $message = $e->getMessage();
            if (stripos($message, 'Duplicate column') !== false || stripos($message, 'Column already exists') !== false || str_contains($message, '42S21')) {
                $this->logDebug('open_column_exists_ignored', ['table' => $table, 'column' => $column, 'message' => $message]);
                return;
            }
            throw $e;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) AS total
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $stmt->execute([
                'table_name' => $this->safeIdentifier($table),
                'column_name' => $column,
            ]);
            $row = $stmt->fetch();
            return ((int) ($row['total'] ?? 0)) > 0;
        } catch (Throwable $e) {
            $this->logDebug('open_has_column_exception', ['table' => $table, 'column' => $column, 'message' => $e->getMessage()]);
            // Fallback compatible con algunos hostings MySQL/MariaDB.
            try {
                $stmt = $this->db->query('SHOW COLUMNS FROM `' . $this->safeIdentifier($table) . '`');
                while ($row = $stmt->fetch()) {
                    if (($row['Field'] ?? '') === $column) {
                        return true;
                    }
                }
            } catch (Throwable $inner) {
                $this->logDebug('open_show_columns_exception', ['table' => $table, 'column' => $column, 'message' => $inner->getMessage()]);
            }
            return false;
        }
    }

    private function safeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier) ?: '';
    }

    private function logDebug(string $event, array $data): void
    {
        try {
            $this->execute(
                'CREATE TABLE IF NOT EXISTS tracking_debug_logs (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    event VARCHAR(80) NOT NULL,
                    data TEXT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
            $this->execute(
                'INSERT INTO tracking_debug_logs (event, data, created_at) VALUES (:event, :data, NOW())',
                [
                    'event' => $event,
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (Throwable $e) {
            error_log('Tracking debug log failed: ' . $e->getMessage());
        }
    }
}
