<?php

declare(strict_types=1);

final class CampaignDeliverySendService
{
    public function processDue(int $limit = 50, ?int $campaignId = null): array
    {
        $deliveryModel = new CampaignDelivery();
        $campaignMessageModel = new CampaignMessage();
        $settings = (new SettingsService())->all();
        $recipientModel = new Recipient();
        $ses = new SesV2Service();
        $tracker = class_exists('CampaignTrackingService') ? new CampaignTrackingService() : null;

        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
        $rows = $deliveryModel->due($limit, $campaignId);

        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $processed++;

            if (($row['campaign_status'] ?? '') !== 'active') {
                $deliveryModel->markSkipped((int) $row['id'], 'Campaña inactiva, detenida o cancelada.');
                $deliveryModel->skipPendingByCampaign((int) $row['campaign_id'], 'Campaña inactiva, detenida o cancelada.');
                $skipped++;
                continue;
            }

            if (empty($row['campaign_recipient_id']) || ($row['campaign_recipient_status'] ?? '') !== 'active') {
                $deliveryModel->markSkipped((int) $row['id'], 'Destinatario eliminado o excluido de la campaña activa.');
                $deliveryModel->skipPendingForRecipient((int) $row['campaign_id'], (int) $row['recipient_id'], 'Destinatario eliminado o excluido de la campaña activa.');
                $skipped++;
                continue;
            }

            if (!empty($row['responded_at'])) {
                $deliveryModel->markSkipped((int) $row['id'], 'Destinatario respondio. Secuencia detenida.');
                $deliveryModel->skipPendingForRecipient((int) $row['campaign_id'], (int) $row['recipient_id'], 'Destinatario respondio. Secuencia detenida.');
                $skipped++;
                continue;
            }

            if (($row['recipient_status'] ?? '') !== 'active' || !empty($row['unsubscribed_at'])) {
                $deliveryModel->markSkipped((int) $row['id'], 'Destinatario inactivo o desuscrito.');
                $deliveryModel->skipPendingForRecipient((int) $row['campaign_id'], (int) $row['recipient_id'], 'Destinatario inactivo o desuscrito.');
                $skipped++;
                continue;
            }

            $message = $campaignMessageModel->findByCampaign((int) $row['campaign_id']);
            if (!$message) {
                $deliveryModel->markFailed((int) $row['id'], 'Mensaje de campaña no encontrado.');
                $failed++;
                continue;
            }

            $token = $recipientModel->generateOrGetToken((int) $row['recipient_id']);
            $unsubscribeUrl = $baseUrl . (($appConfig['unsubscribe_path'] ?? '/unsubscribe/') . $token);

            $subject = (string) $message['subject'];
            $contentMode = (string) ($row['content_mode'] ?? 'text');
            $textBody = trim((string) ($message['text_body'] ?? ''));
            $htmlBody = trim((string) ($message['html_body'] ?? ''));

            $textFooter = "\n\n---\nSi ya no deseas recibir estos correos:\n" . $unsubscribeUrl;
            $htmlFooter = '<hr style="border:none;border-top:1px solid #eee;margin:20px 0"><p style="font-size:12px;color:#888;text-align:center">Si ya no deseas recibir estos correos, <a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">cancela tu suscripción aquí</a>.</p>';

            if ($contentMode === 'html') {
                $htmlToSend = $htmlBody . $htmlFooter;
                $textToSend = $textBody !== '' ? $textBody . $textFooter : strip_tags($htmlBody) . $textFooter;
            } else {
                $textToSend = $textBody . $textFooter;
                $htmlToSend = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8')) . $htmlFooter;
            }

            if ($tracker !== null && method_exists($tracker, 'trackingPixel')) {
                $htmlToSend = $this->appendTrackingPixel(
                    $htmlToSend,
                    $tracker->trackingPixel('cd', (int) $row['id'], (int) $row['campaign_id'], (int) $row['recipient_id'])
                );
            }

            $baseReplyTo = $message['reply_to'] ?: ($settings['ses_reply_to'] ?? '');
            $replyTo = (new ReplyAutomationService())->replyToForRecipient((string) $baseReplyTo, (int) $row['campaign_id'], (int) $row['recipient_id']);

            $result = $ses->send($settings, [
                'to_email' => $row['email'],
                'from_name' => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
                'from_email' => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
                'reply_to' => $replyTo,
                'configuration_set' => $settings['ses_configuration_set'] ?? '',
                'subject' => $subject,
                'html_body' => $htmlToSend,
                'text_body' => $textToSend,
            ]);

            if ($result['ok'] ?? false) {
                $deliveryModel->markSent((int) $row['id'], (string) ($result['message_id'] ?? ''));
                $sent++;
            } else {
                $deliveryModel->markFailed((int) $row['id'], (string) ($result['error'] ?? 'Error desconocido'));
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    private function appendTrackingPixel(string $html, string $pixel): string
    {
        if (stripos($html, '</body>') !== false) {
            return preg_replace('~</body>~i', $pixel . '</body>', $html, 1) ?? ($html . $pixel);
        }
        if (stripos($html, '</html>') !== false) {
            return preg_replace('~</html>~i', $pixel . '</html>', $html, 1) ?? ($html . $pixel);
        }
        return $html . $pixel;
    }
}
