<?php

declare(strict_types=1);

final class SendService
{
    public function start(int $campaignId): int
    {
        $campaignModel = new Campaign();
        $messageModel = new CampaignMessage();
        $sessionModel = new SendSession();
        $campaignRecipientModel = new CampaignRecipient();

        $campaign = $campaignModel->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campaña no encontrada.');
        }

        $message = $messageModel->findByCampaign($campaignId);
        if (!$message) {
            throw new RuntimeException('La campaña no tiene mensaje configurado.');
        }

        $recipients = $campaignRecipientModel->getSendableRecipientsByCampaign($campaignId);
        if (empty($recipients)) {
            throw new RuntimeException('La campaña no tiene destinatarios asignados.');
        }

        $sessionId = $sessionModel->create($campaignId, (int) $message['id']);
        $sessionModel->addItemsFromRecipients($sessionId, $recipients);
        $sessionModel->setStatus($sessionId, 'processing');
        $campaignModel->setStatus($campaignId, 'processing');

        AuditLogger::log('send.started', 'campaign', $campaignId, [
            'send_session_id' => $sessionId,
        ]);

        return $sessionId;
    }

    public function process(int $sessionId, int $limit = 10): array
    {
        $sessionModel = new SendSession();
        $session = $sessionModel->find($sessionId);

        if (!$session) {
            throw new RuntimeException('Sesión de envío no encontrada.');
        }

        if (in_array($session['status'], ['paused', 'completed', 'cancelled'], true)) {
            return $sessionModel->refreshCounts($sessionId);
        }

        $campaignModel = new Campaign();
        $campaign = $campaignModel->find((int) $session['campaign_id']);
        if (!$campaign) {
            throw new RuntimeException('Campaña no encontrada.');
        }

        $campaignMessageModel = new CampaignMessage();
        $message = $campaignMessageModel->findByCampaign((int) $session['campaign_id']);
        if (!$message) {
            throw new RuntimeException('Mensaje de campaña no encontrado.');
        }

        $settings = (new SettingsService())->all();
        $recipientModel = new Recipient();
        $ses = new SesV2Service();
        $batch = $sessionModel->getPendingItems($sessionId, $limit);

        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');
        $contentMode = (string) ($campaign['content_mode'] ?? 'text');

        foreach ($batch as $item) {
            if (($item['recipient_status'] ?? '') !== 'active') {
                $sessionModel->markItemFailed((int) $item['id'], 'Destinatario inactivo.');
                continue;
            }

            if (!empty($item['unsubscribed_at'] ?? null)) {
                $sessionModel->markItemFailed((int) $item['id'], 'Destinatario desuscrito.');
                continue;
            }

            $token = $recipientModel->generateOrGetToken((int) $item['recipient_id']);
            $unsubscribeUrl = $baseUrl . (($appConfig['unsubscribe_path'] ?? '/unsubscribe/') . $token);

            $subject = trim((string) $message['subject']);
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

            $result = $ses->send($settings, [
                'to_email' => $item['email'],
                'from_name' => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
                'from_email' => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
                'reply_to' => $message['reply_to'] ?: ($settings['ses_reply_to'] ?? ''),
                'configuration_set' => $settings['ses_configuration_set'] ?? '',
                'subject' => $subject,
                'html_body' => $htmlToSend,
                'text_body' => $textToSend,
            ]);

            if ($result['ok'] ?? false) {
                $sessionModel->markItemSent((int) $item['id'], (string) ($result['message_id'] ?? ''));
            } else {
                $sessionModel->markItemFailed((int) $item['id'], (string) ($result['error'] ?? 'Error desconocido'));
            }
        }

        $updated = $sessionModel->refreshCounts($sessionId);

        if (($updated['status'] ?? '') === 'completed') {
            $campaignModel->setStatus((int) $session['campaign_id'], 'completed');
        }

        return $updated;
    }
}