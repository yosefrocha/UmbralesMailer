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
        return $this->fetchAll(
            'SELECT cr.id AS campaign_recipient_id,
                    cr.status AS campaign_status,
                    cr.assigned_at,
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
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
            ]
        );

        if ($existing) {
            $this->execute(
                'UPDATE campaign_recipients
                 SET status = "active",
                     source = :source,
                     import_id = :import_id
                 WHERE id = :id',
                [
                    'id' => $existing['id'],
                    'source' => $source,
                    'import_id' => $importId,
                ]
            );
            return;
        }

        $this->execute(
            'INSERT INTO campaign_recipients (campaign_id, recipient_id, source, import_id, status)
             VALUES (:campaign_id, :recipient_id, :source, :import_id, "active")',
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
                'source' => $source,
                'import_id' => $importId,
            ]
        );
    }

    public function remove(int $campaignId, int $recipientId): void
    {
        $this->execute(
            'DELETE FROM campaign_recipients
             WHERE campaign_id = :campaign_id
               AND recipient_id = :recipient_id',
            [
                'campaign_id' => $campaignId,
                'recipient_id' => $recipientId,
            ]
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
    $rows = $this->fetchAll(
        'SELECT recipient_id
         FROM campaign_recipients
         WHERE campaign_id = :campaign_id
           AND status = "active"
         ORDER BY id ASC',
        ['campaign_id' => $campaignId]
    );

    return array_map(
        static fn (array $row): int => (int) $row['recipient_id'],
        $rows
    );
}
}