<?php

declare(strict_types=1);

final class CampaignScheduleService
{
    public function rebuildDeliveries(int $campaignId): void
    {
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign || empty($campaign['sequence_start_at'])) {
            return;
        }

        $deliveryModel = new CampaignDelivery();
        $campaignRecipientModel = new CampaignRecipient();

        $deliveryModel->deletePendingByCampaign($campaignId);

        $recipientIds = $campaignRecipientModel->recipientIdsForScheduling($campaignId);

        $baseDate = new DateTimeImmutable((string) $campaign['sequence_start_at']);
        $intervalDays = (int) ($campaign['sequence_interval_days'] ?? 2);
        $totalSends = (int) ($campaign['sequence_total_sends'] ?? 10);

        foreach ($recipientIds as $recipientId) {
            for ($sendNumber = 1; $sendNumber <= $totalSends; $sendNumber++) {
                $scheduledFor = $baseDate->modify('+' . (($sendNumber - 1) * $intervalDays) . ' days');

                $deliveryModel->schedule(
                    $campaignId,
                    $recipientId,
                    $sendNumber,
                    $scheduledFor->format('Y-m-d H:i:s')
                );
            }
        }
    }
}