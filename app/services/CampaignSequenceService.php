<?php

declare(strict_types=1);

final class CampaignSequenceService
{
    public function saveSequence(int $campaignId, string $contentMode, string $startAt, array $steps): void
    {
        $campaignModel = new Campaign();
        $stepModel = new CampaignStep();

        $campaignModel->updateSequenceSettings($campaignId, [
            'content_mode' => $contentMode,
            'sequence_start_at' => $startAt,
            'sequence_interval_days' => 2,
            'sequence_total_steps' => 10,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $step = $steps[$i] ?? null;

            if (!$step) {
                continue;
            }

            $stepModel->upsertStep($campaignId, $i, [
                'subject' => trim((string) ($step['subject'] ?? '')) ?: 'Mensaje ' . $i,
                'text_body' => (string) ($step['text_body'] ?? ''),
                'html_body' => (string) ($step['html_body'] ?? ''),
                'is_active' => (int) ($step['is_active'] ?? 1),
            ]);
        }

        $this->rebuildDeliveries($campaignId);
    }

    public function rebuildDeliveries(int $campaignId): void
    {
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign || empty($campaign['sequence_start_at'])) {
            return;
        }

        $campaignRecipientModel = new CampaignRecipient();
        $deliveryModel = new CampaignDelivery();

        $recipients = $campaignRecipientModel->getSendableRecipientsByCampaign($campaignId);

        $deliveryModel->deletePendingByCampaign($campaignId);

        $baseDate = new DateTimeImmutable((string) $campaign['sequence_start_at']);
        $intervalDays = (int) ($campaign['sequence_interval_days'] ?? 2);
        $totalSteps = (int) ($campaign['sequence_total_steps'] ?? 10);

        foreach ($recipients as $recipient) {
            for ($step = 1; $step <= $totalSteps; $step++) {
                $scheduledFor = $baseDate->modify('+' . (($step - 1) * $intervalDays) . ' days');

                $deliveryModel->schedule(
                    $campaignId,
                    (int) $recipient['id'],
                    $step,
                    $scheduledFor->format('Y-m-d H:i:s')
                );
            }
        }
    }
}