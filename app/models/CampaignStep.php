<?php

declare(strict_types=1);

final class CampaignStep extends Model
{
    public function allByCampaign(int $campaignId): array
    {
        return $this->fetchAll(
            'SELECT * FROM campaign_steps WHERE campaign_id = :campaign_id ORDER BY step_number ASC',
            ['campaign_id' => $campaignId]
        );
    }

    public function seedDefaults(int $campaignId): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $existing = $this->fetchOne(
                'SELECT id FROM campaign_steps WHERE campaign_id = :campaign_id AND step_number = :step_number LIMIT 1',
                [
                    'campaign_id' => $campaignId,
                    'step_number' => $i,
                ]
            );

            if ($existing) {
                continue;
            }

            $this->execute(
                'INSERT INTO campaign_steps (campaign_id, step_number, subject, text_body, html_body, is_active)
                 VALUES (:campaign_id, :step_number, :subject, :text_body, :html_body, 1)',
                [
                    'campaign_id' => $campaignId,
                    'step_number' => $i,
                    'subject' => 'Mensaje ' . $i,
                    'text_body' => "Hola,\n\nEscribe aquí el mensaje " . $i . ".\n\nSaludos,\nEquipo Umbrales",
                    'html_body' => '<p>Hola,</p><p>Escribe aquí el mensaje ' . $i . '.</p><p>Saludos,<br>Equipo Umbrales</p>',
                ]
            );
        }
    }

    public function upsertStep(int $campaignId, int $stepNumber, array $data): void
    {
        $existing = $this->fetchOne(
            'SELECT id FROM campaign_steps WHERE campaign_id = :campaign_id AND step_number = :step_number LIMIT 1',
            [
                'campaign_id' => $campaignId,
                'step_number' => $stepNumber,
            ]
        );

        if ($existing) {
            $this->execute(
                'UPDATE campaign_steps
                 SET subject = :subject,
                     text_body = :text_body,
                     html_body = :html_body,
                     is_active = :is_active
                 WHERE id = :id',
                [
                    'id' => $existing['id'],
                    'subject' => $data['subject'],
                    'text_body' => $data['text_body'],
                    'html_body' => $data['html_body'],
                    'is_active' => $data['is_active'],
                ]
            );
            return;
        }

        $this->execute(
            'INSERT INTO campaign_steps (campaign_id, step_number, subject, text_body, html_body, is_active)
             VALUES (:campaign_id, :step_number, :subject, :text_body, :html_body, :is_active)',
            [
                'campaign_id' => $campaignId,
                'step_number' => $stepNumber,
                'subject' => $data['subject'],
                'text_body' => $data['text_body'],
                'html_body' => $data['html_body'],
                'is_active' => $data['is_active'],
            ]
        );
    }
}