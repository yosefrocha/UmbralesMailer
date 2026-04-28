<?php

declare(strict_types=1);

final class CampaignScheduleService
{
    public function scheduleDrip(int $campaignId, string $startAt, int $quotaPerRecipient, int $intervalDays): array
    {
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campaña no encontrada.');
        }

        if (($campaign['status'] ?? '') === 'cancelled') {
            throw new RuntimeException('No se puede programar una campaña cancelada.');
        }

        $message = (new CampaignMessage())->findByCampaign($campaignId);
        if (!$message) {
            throw new RuntimeException('Antes de programar debes guardar el mensaje de la campaña.');
        }

        $quotaPerRecipient = max(1, min(60, $quotaPerRecipient));
        $intervalDays = max(1, min(365, $intervalDays));

        try {
            $baseDate = new DateTimeImmutable($startAt);
        } catch (Throwable) {
            throw new RuntimeException('La fecha de inicio no es válida.');
        }

        $deliveryModel = new CampaignDelivery();
        $campaignRecipientModel = new CampaignRecipient();

        $deliveryModel->deletePendingByCampaign($campaignId);
        $recipientIds = $campaignRecipientModel->recipientIdsForScheduling($campaignId);

        if (empty($recipientIds)) {
            throw new RuntimeException('La campaña no tiene destinatarios activos asignados, o todos ya respondieron / se desuscribieron.');
        }

        $createdOrUpdated = 0;
        foreach ($recipientIds as $recipientId) {
            for ($sendNumber = 1; $sendNumber <= $quotaPerRecipient; $sendNumber++) {
                $daysToAdd = ($sendNumber - 1) * $intervalDays;
                $scheduledFor = $baseDate->modify('+' . $daysToAdd . ' days');
                $deliveryModel->schedule($campaignId, (int) $recipientId, $sendNumber, $scheduledFor->format('Y-m-d H:i:s'));
                $createdOrUpdated++;
            }
        }

        (new Campaign())->activateCampaign($campaignId);
        $this->ensureCronKey();

        AuditLogger::log('campaign.schedule.drip_created', 'campaign', $campaignId, [
            'start_at' => $baseDate->format('Y-m-d H:i:s'),
            'quota_per_recipient' => $quotaPerRecipient,
            'interval_days' => $intervalDays,
            'recipients' => count($recipientIds),
            'deliveries' => $createdOrUpdated,
        ]);

        return [
            'scheduled' => $createdOrUpdated,
            'recipients' => count($recipientIds),
            'quota_per_recipient' => $quotaPerRecipient,
            'interval_days' => $intervalDays,
            'start_at' => $baseDate->format('Y-m-d H:i:s'),
        ];
    }

    /** Compatibility wrapper: old calls now create a drip sequence. */
    public function scheduleBatches(int $campaignId, string $startAt, int $batchSize, int $intervalDays): array
    {
        return $this->scheduleDrip($campaignId, $startAt, $batchSize, $intervalDays);
    }

    public function cancelPending(int $campaignId): int
    {
        $cancelled = (new CampaignDelivery())->cancelPendingByCampaign($campaignId);
        AuditLogger::log('campaign.schedule.cancelled', 'campaign', $campaignId, ['cancelled_pending' => $cancelled]);
        return $cancelled;
    }

    public function ensureCronKey(): string
    {
        $settings = (new SettingsService())->all();
        $key = trim((string) ($settings['scheduled_cron_key'] ?? ''));
        if ($key !== '') {
            return $key;
        }

        $key = bin2hex(random_bytes(24));
        (new Setting())->set('scheduled_cron_key', $key);
        return $key;
    }

    public function cronUrl(): string
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
        return $baseUrl . '/cron/scheduled-send?key=' . $this->ensureCronKey();
    }
}
