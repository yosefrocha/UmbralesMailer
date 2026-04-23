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
                     error_message = NULL
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

    public function due(int $limit = 50): array
    {
        $sql = 'SELECT cd.*,
                       c.status AS campaign_status,
                       c.content_mode,
                       c.sequence_total_sends,
                       r.email,
                       r.status AS recipient_status,
                       r.unsubscribed_at,
                       cr.status AS campaign_recipient_status
                FROM campaign_deliveries cd
                INNER JOIN campaigns c
                    ON c.id = cd.campaign_id
                INNER JOIN recipients r
                    ON r.id = cd.recipient_id
                INNER JOIN campaign_recipients cr
                    ON cr.campaign_id = cd.campaign_id
                   AND cr.recipient_id = cd.recipient_id
                WHERE cd.status = "pending"
                  AND cd.scheduled_for <= NOW()
                ORDER BY cd.scheduled_for ASC
                LIMIT ' . max(1, $limit);

        return $this->fetchAll($sql);
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

    public function markSent(int $id, string $messageId): void
    {
        $this->execute(
            'UPDATE campaign_deliveries
             SET status = "sent",
                 ses_message_id = :ses_message_id,
                 sent_at = NOW()
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
                 error_message = :error_message
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
                 error_message = :error_message
             WHERE id = :id',
            [
                'id' => $id,
                'error_message' => mb_substr($reason, 0, 500),
            ]
        );
    }
}