<?php

declare(strict_types=1);

final class SendSession extends Model
{
    public function create(int $campaignId, int $messageId): int
    {
        $this->execute('INSERT INTO send_sessions (campaign_id, campaign_message_id, status, started_at) VALUES (:campaign_id, :campaign_message_id, "queued", NOW())', [
            'campaign_id' => $campaignId,
            'campaign_message_id' => $messageId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT ss.*, c.name AS campaign_name FROM send_sessions ss INNER JOIN campaigns c ON c.id = ss.campaign_id WHERE ss.id = :id LIMIT 1';
        return $this->fetchOne($sql, ['id' => $id]);
    }

    public function latestByCampaign(int $campaignId): ?array
    {
        return $this->fetchOne('SELECT * FROM send_sessions WHERE campaign_id = :campaign_id ORDER BY id DESC LIMIT 1', ['campaign_id' => $campaignId]);
    }

    public function addItemsFromActiveRecipients(int $sessionId): void
    {
        $sql = 'INSERT IGNORE INTO send_session_items (send_session_id, recipient_id)
                SELECT :session_id, r.id
                FROM recipients r
                WHERE r.unsubscribed_at IS NULL AND r.status = "active"';
        $this->execute($sql, ['session_id' => $sessionId]);
        $this->refreshCounts($sessionId);
    }

    public function getPendingItems(int $sessionId, int $limit): array
    {
        $sql = 'SELECT ssi.*, r.email, r.first_name, r.last_name, r.institution, r.country, r.segment, r.status AS recipient_status, r.consent_at
                FROM send_session_items ssi
                INNER JOIN recipients r ON r.id = ssi.recipient_id
                WHERE ssi.send_session_id = :session_id AND ssi.status = "pending"
                ORDER BY ssi.id ASC
                LIMIT ' . max(1, $limit);
        return $this->fetchAll($sql, ['session_id' => $sessionId]);
    }

    public function markItemSent(int $itemId, string $messageId): void
    {
        $this->execute('UPDATE send_session_items SET ses_message_id = :ses_message_id, status = "sent", processed_at = NOW(), updated_at = NOW() WHERE id = :id', [
            'id' => $itemId,
            'ses_message_id' => $messageId,
        ]);
    }

    public function markItemFailed(int $itemId, string $error): void
    {
        $this->execute('UPDATE send_session_items SET status = "failed", error_message = :error_message, processed_at = NOW(), updated_at = NOW() WHERE id = :id', [
            'id' => $itemId,
            'error_message' => mb_substr($error, 0, 250),
        ]);
    }

    public function refreshCounts(int $sessionId): array
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total_count, SUM(CASE WHEN status IN ("sent","failed","skipped") THEN 1 ELSE 0 END) AS processed_count, SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) AS success_count, SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) AS failed_count, SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count FROM send_session_items WHERE send_session_id = :session_id', ['session_id' => $sessionId]);
        $pending = (int) ($row['pending_count'] ?? 0);
        $status = 'processing';
        $current = $this->find($sessionId);
        if (($current['status'] ?? '') === 'paused') {
            $status = 'paused';
        } elseif ($pending === 0) {
            $status = 'completed';
        }
        $this->execute('UPDATE send_sessions SET total_count = :total_count, processed_count = :processed_count, success_count = :success_count, failed_count = :failed_count, status = :status, finished_at = IF(:status = "completed", NOW(), finished_at), updated_at = NOW() WHERE id = :id', [
            'id' => $sessionId,
            'total_count' => (int) ($row['total_count'] ?? 0),
            'processed_count' => (int) ($row['processed_count'] ?? 0),
            'success_count' => (int) ($row['success_count'] ?? 0),
            'failed_count' => (int) ($row['failed_count'] ?? 0),
            'status' => $status,
        ]);
        return $this->find($sessionId) ?? [];
    }

    public function setStatus(int $sessionId, string $status): void
    {
        $sql = 'UPDATE send_sessions SET status = :status, updated_at = NOW()';
        if ($status === 'paused') {
            $sql .= ', paused_at = NOW()';
        }
        if ($status === 'processing') {
            $sql .= ', resumed_at = NOW()';
        }
        $sql .= ' WHERE id = :id';
        $this->execute($sql, ['id' => $sessionId, 'status' => $status]);
    }

    public function recentItems(int $sessionId, int $limit = 20): array
    {
        $sql = 'SELECT ssi.*, r.email FROM send_session_items ssi INNER JOIN recipients r ON r.id = ssi.recipient_id WHERE ssi.send_session_id = :session_id ORDER BY ssi.id DESC LIMIT ' . max(1, $limit);
        return $this->fetchAll($sql, ['session_id' => $sessionId]);
    }
}
