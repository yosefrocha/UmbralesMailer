<?php

declare(strict_types=1);

final class CampaignMessage extends Model
{
    public function findByCampaign(int $campaignId): ?array
    {
        return $this->fetchOne('SELECT * FROM campaign_messages WHERE campaign_id = :campaign_id LIMIT 1', ['campaign_id' => $campaignId]);
    }

    public function saveForCampaign(int $campaignId, array $data): void
    {
        $existing = $this->findByCampaign($campaignId);
        $params = [
            'campaign_id' => $campaignId,
            'subject' => $data['subject'],
            'from_name' => $data['from_name'],
            'from_email' => $data['from_email'],
            'reply_to' => $data['reply_to'] ?: null,
            'html_body' => $data['html_body'],
            'text_body' => $data['text_body'],
        ];

        if ($existing) {
            $params['id'] = $existing['id'];
            $this->execute('UPDATE campaign_messages SET subject = :subject, from_name = :from_name, from_email = :from_email, reply_to = :reply_to, html_body = :html_body, text_body = :text_body WHERE id = :id', $params);
            return;
        }

        $this->execute('INSERT INTO campaign_messages (campaign_id, subject, from_name, from_email, reply_to, html_body, text_body) VALUES (:campaign_id, :subject, :from_name, :from_email, :reply_to, :html_body, :text_body)', $params);
    }
}
