<?php

declare(strict_types=1);

final class CampaignRecipient extends Model
{
    public function countActiveByCampaign(int $campaignId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             WHERE cr.campaign_id = :campaign_id
               AND cr.status = "active"
               AND r.status = "active"
               AND r.unsubscribed_at IS NULL',
            ['campaign_id' => $campaignId]
        );
        return (int) ($row['total'] ?? 0);
    }

    public function allByCampaign(int $campaignId): array
    {
        $selectExtra = $this->hasColumn('campaign_recipients', 'responded_at')
            ? ', cr.responded_at, cr.response_note, cr.stopped_at, cr.stop_reason'
            : ', NULL AS responded_at, NULL AS response_note, NULL AS stopped_at, NULL AS stop_reason';

        return $this->fetchAll(
            'SELECT cr.id AS campaign_recipient_id,
                    cr.status AS campaign_status,
                    cr.assigned_at' . $selectExtra . ',
                    r.*
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             WHERE cr.campaign_id = :campaign_id
             ORDER BY cr.id DESC',
            ['campaign_id' => $campaignId]
        );
    }

    public function attach(int $campaignId, int $recipientId, string $source = 'csv', ?int $importId = null): void
    {
        $existing = $this->fetchOne(
            'SELECT id
             FROM campaign_recipients
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id
             LIMIT 1',
            ['campaign_id' => $campaignId, 'recipient_id' => $recipientId]
        );

        if ($existing) {
            $this->execute(
                'UPDATE campaign_recipients
                 SET status = "active",
                     source = :source,
                     import_id = :import_id
                 WHERE id = :id',
                ['id' => $existing['id'], 'source' => $source, 'import_id' => $importId]
            );
            return;
        }

        $this->execute(
            'INSERT INTO campaign_recipients (campaign_id, recipient_id, source, import_id, status)
             VALUES (:campaign_id, :recipient_id, :source, :import_id, "active")',
            ['campaign_id' => $campaignId, 'recipient_id' => $recipientId, 'source' => $source, 'import_id' => $importId]
        );
    }

    public function remove(int $campaignId, int $recipientId): void
    {
        if ($this->hasColumn('campaign_recipients', 'stopped_at')) {
            $this->execute(
                'UPDATE campaign_recipients
                 SET status = "excluded",
                     stopped_at = NOW(),
                     stop_reason = "Eliminado de la campana activa"
                 WHERE campaign_id = :campaign_id
                   AND recipient_id = :recipient_id',
                ['campaign_id' => $campaignId, 'recipient_id' => $recipientId]
            );
            if (class_exists('CampaignDelivery')) {
                (new CampaignDelivery())->skipPendingForRecipient($campaignId, $recipientId, 'Destinatario eliminado de la campana activa.');
            }
            return;
        }

        $this->execute(
            'DELETE FROM campaign_recipients
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id',
            ['campaign_id' => $campaignId, 'recipient_id' => $recipientId]
        );
    }

    public function getSendableRecipientsByCampaign(int $campaignId): array
    {
        return $this->fetchAll(
            'SELECT r.*
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             WHERE cr.campaign_id = :campaign_id
               AND cr.status = "active"
               AND r.status = "active"
               AND r.unsubscribed_at IS NULL
             ORDER BY cr.id ASC',
            ['campaign_id' => $campaignId]
        );
    }

    public function recipientIdsForScheduling(int $campaignId): array
    {
        $respondedFilter = $this->hasColumn('campaign_recipients', 'responded_at') ? ' AND cr.responded_at IS NULL' : '';
        $rows = $this->fetchAll(
            'SELECT cr.recipient_id
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             WHERE cr.campaign_id = :campaign_id
               AND cr.status = "active"
               AND r.status = "active"
               AND r.unsubscribed_at IS NULL' . $respondedFilter . '
             ORDER BY cr.id ASC',
            ['campaign_id' => $campaignId]
        );
        return array_map(static fn (array $row): int => (int) $row['recipient_id'], $rows);
    }

    public function markResponded(int $campaignId, int $recipientId, string $note = ''): int
    {
        $this->requireResponseColumns();

        $stmt = $this->db->prepare(
            'UPDATE campaign_recipients
             SET responded_at = COALESCE(responded_at, NOW()),
                 response_note = :response_note,
                 stopped_at = COALESCE(stopped_at, NOW()),
                 stop_reason = "Respondio"
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id
               AND status = "active"'
        );
        $stmt->execute([
            'campaign_id' => $campaignId,
            'recipient_id' => $recipientId,
            'response_note' => mb_substr($note, 0, 500),
        ]);

        $affected = $stmt->rowCount();
        if (class_exists('CampaignDelivery')) {
            (new CampaignDelivery())->skipPendingForRecipient($campaignId, $recipientId, 'Destinatario respondio. Secuencia detenida.');
        }
        return $affected;
    }

    public function markRespondedByEmail(string $email, string $note = ''): int
    {
        $this->requireResponseColumns();
        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        $rows = $this->fetchAll(
            'SELECT cr.campaign_id, cr.recipient_id
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             INNER JOIN campaigns c ON c.id = cr.campaign_id
             WHERE LOWER(r.email) = :email
               AND cr.status = "active"
               AND r.status = "active"
               AND r.unsubscribed_at IS NULL
               AND cr.responded_at IS NULL
               AND c.status IN ("active", "processing")',
            ['email' => $email]
        );

        $total = 0;
        foreach ($rows as $row) {
            $total += $this->markResponded((int) $row['campaign_id'], (int) $row['recipient_id'], $note);
        }
        return $total;
    }

    public function clearResponded(int $campaignId, int $recipientId): void
    {
        $this->requireResponseColumns();
        $this->execute(
            'UPDATE campaign_recipients
             SET responded_at = NULL,
                 response_note = NULL,
                 stopped_at = NULL,
                 stop_reason = NULL
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id',
            ['campaign_id' => $campaignId, 'recipient_id' => $recipientId]
        );
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $row = $this->fetchOne(
                'SELECT COUNT(*) AS total
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name',
                ['table_name' => $table, 'column_name' => $column]
            );
            return (int) ($row['total'] ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function requireResponseColumns(): void
    {
        if (!$this->hasColumn('campaign_recipients', 'responded_at')) {
            throw new RuntimeException('Falta ejecutar la migracion de secuencia: campaign_recipients.responded_at.');
        }
    }
}
