<?php

declare(strict_types=1);

final class CampaignMessage extends Model
{
    public function findByCampaign(int $campaignId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM campaign_messages WHERE campaign_id = :campaign_id LIMIT 1',
            ['campaign_id' => $campaignId]
        );
    }

    public function saveForCampaign(int $campaignId, array $data): void
    {
        $existing = $this->findByCampaign($campaignId);

        $subject = trim((string) ($data['subject'] ?? ''));
        $fromName = trim((string) ($data['from_name'] ?? ''));
        $fromEmail = trim((string) ($data['from_email'] ?? ''));
        $replyTo = trim((string) ($data['reply_to'] ?? ''));
        $htmlBody = trim((string) ($data['html_body'] ?? ''));
        $textBody = trim((string) ($data['text_body'] ?? ''));

        $params = [
            'campaign_id' => $campaignId,
            'subject' => $subject,
            'from_name' => $fromName !== '' ? $fromName : null,
            'from_email' => $fromEmail,
            'reply_to' => $replyTo !== '' ? $replyTo : null,
            'html_body' => $htmlBody !== '' ? $htmlBody : null,
            'text_body' => $textBody !== '' ? $textBody : null,
        ];

        if ($existing) {
            $params['id'] = (int) $existing['id'];

            $this->execute(
                'UPDATE campaign_messages
                 SET subject = :subject,
                     from_name = :from_name,
                     from_email = :from_email,
                     reply_to = :reply_to,
                     html_body = :html_body,
                     text_body = :text_body
                 WHERE id = :id',
                $params
            );
            return;
        }

        $this->execute(
            'INSERT INTO campaign_messages (
                campaign_id,
                subject,
                from_name,
                from_email,
                reply_to,
                html_body,
                text_body
             ) VALUES (
                :campaign_id,
                :subject,
                :from_name,
                :from_email,
                :reply_to,
                :html_body,
                :text_body
             )',
            $params
        );
    }
}