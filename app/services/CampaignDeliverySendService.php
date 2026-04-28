<?php

declare(strict_types=1);

final class CampaignDeliverySendService
{
    public function processDue(int $limit = 50): array
    {
        $deliveryModel = new CampaignDelivery();
        $campaignMessageModel = new CampaignMessage();
        $settings = (new SettingsService())->all();
        $recipientModel = new Recipient();
        $ses = new SesV2Service();
        $tracker = new CampaignTrackingService();

        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
        $rows = $deliveryModel->due($limit);

        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $processed++;

            if (($row['campaign_status'] ?? '') !== 'active') {
                $deliveryModel->markSkipped((int) $row['id'], 'Campaña inactiva o no activa.');
                $skipped++;
                continue;
            }

            if (($row['campaign_recipient_status'] ?? '') !== 'active') {
                $deliveryModel->markSkipped((int) $row['id'], 'Destinatario removido o excluido de la campaña.');
                $skipped++;
                continue;
            }

            if (($row['recipient_status'] ?? '') !== 'active' || !empty($row['unsubscribed_at'])) {
                $deliveryModel->markSkipped((int) $row['id'], 'Destinatario inactivo o desuscrito.');
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

            $textFooter = "\n\n---\nSi ya no deseas recibir estos correos, utiliza este enlace de baja:\n" . $unsubscribeUrl;
            $htmlFooter = '<hr><p>Si ya no deseas recibir estos correos, utiliza este enlace de baja: <a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">Cancelar suscripción</a></p>';

            if ($contentMode === 'html') {
                $htmlToSend = $htmlBody . $htmlFooter;
                $textToSend = $textBody !== '' ? $textBody . $textFooter : strip_tags($htmlBody) . $textFooter;
            } else {
                $textToSend = $textBody . $textFooter;
                $htmlToSend = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8')) . $htmlFooter;
            }

            $htmlToSend = $this->appendTrackingPixel($htmlToSend, $tracker->trackingPixel('cd', (int) $row['id'], (int) $row['campaign_id'], (int) $row['recipient_id']));

            $result = $ses->send($settings, [
                'to_email' => $row['email'],
                'from_name' => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
                'from_email' => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
                'reply_to' => $message['reply_to'] ?: ($settings['ses_reply_to'] ?? ''),
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
        if (stripos($html, "</body>") !== false) {
            return preg_replace("~</body>~i", $pixel . "</body>", $html, 1) ?? ($html . $pixel);
        }

        if (stripos($html, "</html>") !== false) {
            return preg_replace("~</html>~i", $pixel . "</html>", $html, 1) ?? ($html . $pixel);
        }

        return $html . $pixel;
    }
}
