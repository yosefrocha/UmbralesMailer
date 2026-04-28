<?php

declare(strict_types=1);

final class CampaignDelivery extends Model
{
    public function deletePendingByCampaign(int $campaignId): void
    {
        $this->execute(
            'DELETE FROM campaign_deliveries
             WHERE campaign_id = :campaign_id
               AND status = "pending"',
            ['campaign_id' => $campaignId]
        );
    }

    public function cancelPendingByCampaign(int $campaignId): int
    {
        $stmt = $this->db->prepare(
            'UPDATE campaign_deliveries
             SET status = "cancelled",
                 error_message = "Programacion cancelada por el usuario",
                 updated_at = NOW()
             WHERE campaign_id = :campaign_id
               AND status = "pending"'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        return $stmt->rowCount();
    }

    public function skipPendingByCampaign(int $campaignId, string $reason): int
    {
        $stmt = $this->db->prepare(
            'UPDATE campaign_deliveries
             SET status = "skipped",
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE campaign_id = :campaign_id
               AND status = "pending"'
        );
        $stmt->execute([
            'campaign_id' => $campaignId,
            'error_message' => mb_substr($reason, 0, 500),
        ]);
        return $stmt->rowCount();
    }

    public function skipPendingForRecipient(int $campaignId, int $recipientId, string $reason): int
    {
        $stmt = $this->db->prepare(
            'UPDATE campaign_deliveries
             SET status = "skipped",
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id
               AND status = "pending"'
        );
        $stmt->execute([
            'campaign_id' => $campaignId,
            'recipient_id' => $recipientId,
            'error_message' => mb_substr($reason, 0, 500),
        ]);
        return $stmt->rowCount();
    }

    public function schedule(int $campaignId, int $recipientId, int $sendNumber, string $scheduledFor): void
    {
        $existing = $this->fetchOne(
            'SELECT id, status
             FROM campaign_deliveries
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id
               AND send_number = :send_number
             LIMIT 1',
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
                'send_number' => $sendNumber,
            ]
        );

        if ($existing) {
            if (($existing['status'] ?? '') === 'sent') {
                return;
            }

            $this->execute(
                'UPDATE campaign_deliveries
                 SET scheduled_for = :scheduled_for,
                     status = "pending",
                     error_message = NULL,
                     ses_message_id = NULL,
                     sent_at = NULL,
                     updated_at = NOW()
                 WHERE id = :id',
                [
                    'id' => $existing['id'],
                    'scheduled_for' => $scheduledFor,
                ]
            );
            return;
        }

        $this->execute(
            'INSERT INTO campaign_deliveries (campaign_id, recipient_id, send_number, scheduled_for, status)
             VALUES (:campaign_id, :recipient_id, :send_number, :scheduled_for, "pending")',
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
                'send_number' => $sendNumber,
                'scheduled_for' => $scheduledFor,
            ]
        );
    }

    public function due(int $limit = 50, ?int $campaignId = null): array
    {
        $params = [];
        $campaignFilter = '';
        if ($campaignId !== null && $campaignId > 0) {
            $campaignFilter = ' AND cd.campaign_id = :campaign_id';
            $params['campaign_id'] = $campaignId;
        }

        $respondedSelect = $this->hasColumn('campaign_recipients', 'responded_at')
            ? 'cr.responded_at AS responded_at,'
            : 'NULL AS responded_at,';

        $sql = 'SELECT cd.*,
                       c.status AS campaign_status,
                       c.content_mode,
                       r.email,
                       r.status AS recipient_status,
                       r.unsubscribed_at,
                       cr.status AS campaign_recipient_status,
                       ' . $respondedSelect . '
                       cr.id AS campaign_recipient_id
                FROM campaign_deliveries cd
                INNER JOIN campaigns c
                    ON c.id = cd.campaign_id
                INNER JOIN recipients r
                    ON r.id = cd.recipient_id
                LEFT JOIN campaign_recipients cr
                    ON cr.campaign_id = cd.campaign_id
                   AND cr.recipient_id = cd.recipient_id
                WHERE cd.status = "pending"
                  AND cd.scheduled_for <= NOW()' . $campaignFilter . '
                ORDER BY cd.scheduled_for ASC, cd.id ASC
                LIMIT ' . max(1, min(500, $limit));

        return $this->fetchAll($sql, $params);
    }

    public function countPendingByCampaign(int $campaignId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM campaign_deliveries
             WHERE campaign_id = :campaign_id
               AND status = "pending"',
            ['campaign_id' => $campaignId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function summaryByCampaign(int $campaignId): array
    {
        $rows = $this->fetchAll(
            'SELECT status, COUNT(*) AS total
             FROM campaign_deliveries
             WHERE campaign_id = :campaign_id
             GROUP BY status',
            ['campaign_id' => $campaignId]
        );

        $summary = [
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'cancelled' => 0,
            'due' => 0,
            'next_scheduled_for' => null,
            'last_scheduled_for' => null,
            'responded' => 0,
            'recipients_with_deliveries' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $total = (int) ($row['total'] ?? 0);
            if (array_key_exists($status, $summary)) {
                $summary[$status] = $total;
            }
            $summary['total'] += $total;
        }

        $due = $this->fetchOne(
            'SELECT COUNT(*) AS total
             FROM campaign_deliveries
             WHERE campaign_id = :campaign_id
               AND status = "pending"
               AND scheduled_for <= NOW()',
            ['campaign_id' => $campaignId]
        );
        $summary['due'] = (int) ($due['total'] ?? 0);

        $dates = $this->fetchOne(
            'SELECT MIN(CASE WHEN status = "pending" THEN scheduled_for END) AS next_scheduled_for,
                    MAX(scheduled_for) AS last_scheduled_for,
                    COUNT(DISTINCT recipient_id) AS recipients_with_deliveries
             FROM campaign_deliveries
             WHERE campaign_id = :campaign_id',
            ['campaign_id' => $campaignId]
        );

        $summary['next_scheduled_for'] = $dates['next_scheduled_for'] ?? null;
        $summary['last_scheduled_for'] = $dates['last_scheduled_for'] ?? null;
        $summary['recipients_with_deliveries'] = (int) ($dates['recipients_with_deliveries'] ?? 0);

        if ($this->hasColumn('campaign_recipients', 'responded_at')) {
            $responded = $this->fetchOne(
                'SELECT COUNT(*) AS total
                 FROM campaign_recipients
                 WHERE campaign_id = :campaign_id
                   AND responded_at IS NOT NULL',
                ['campaign_id' => $campaignId]
            );
            $summary['responded'] = (int) ($responded['total'] ?? 0);
        }

        return $summary;
    }

    public function listByCampaign(int $campaignId, int $limit = 200): array
    {
        $respondedSelect = $this->hasColumn('campaign_recipients', 'responded_at')
            ? ', cr.responded_at, cr.response_note, cr.stop_reason'
            : ', NULL AS responded_at, NULL AS response_note, NULL AS stop_reason';

        return $this->fetchAll(
            'SELECT cd.*, r.email, r.first_name, r.last_name, r.institution, r.segment' . $respondedSelect . '
             FROM campaign_deliveries cd
             INNER JOIN recipients r ON r.id = cd.recipient_id
             LEFT JOIN campaign_recipients cr
               ON cr.campaign_id = cd.campaign_id
              AND cr.recipient_id = cd.recipient_id
             WHERE cd.campaign_id = :campaign_id
             ORDER BY cd.scheduled_for ASC, r.email ASC, cd.send_number ASC, cd.id ASC
             LIMIT ' . max(1, min(1000, $limit)),
            ['campaign_id' => $campaignId]
        );
    }

    public function markSent(int $id, string $messageId): void
    {
        $this->execute(
            'UPDATE campaign_deliveries
             SET status = "sent",
                 ses_message_id = :ses_message_id,
                 sent_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'ses_message_id' => $messageId,
            ]
        );
    }

    public function markFailed(int $id, string $error): void
    {
        $this->execute(
            'UPDATE campaign_deliveries
             SET status = "failed",
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'error_message' => mb_substr($error, 0, 500),
            ]
        );
    }

    public function markSkipped(int $id, string $reason): void
    {
        $this->execute(
            'UPDATE campaign_deliveries
             SET status = "skipped",
                 error_message = :error_message,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'id' => $id,
                'error_message' => mb_substr($reason, 0, 500),
            ]
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
}
