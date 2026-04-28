<?php

declare(strict_types=1);

final class SendService
{
    private const MAX_BOUNCE_RATE = 0.05;

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

        AuditLogger::log('send.started', 'campaign', $campaignId, ['send_session_id' => $sessionId]);

        return $sessionId;
    }

    public function retryFailed(int $sessionId): array
    {
        $sessionModel = new SendSession();
        $session = $sessionModel->find($sessionId);

        if (!$session) {
            throw new RuntimeException('Sesión de envío no encontrada.');
        }

        $sessionModel->resetFailedItems($sessionId);
        $sessionModel->setStatus($sessionId, 'processing');

        AuditLogger::log('send.retry', 'send_session', $sessionId);

        return $sessionModel->refreshCounts($sessionId);
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
            // Verificar tasa de rebotes antes de cada envío
            $counts = $sessionModel->refreshCounts($sessionId);
            if ($this->bounceRateExceeded($counts)) {
                $sessionModel->setStatus($sessionId, 'paused');
                $campaignModel->setStatus((int) $session['campaign_id'], 'inactive');
                AuditLogger::log('send.auto_paused', 'send_session', $sessionId, ['reason' => 'bounce_rate_exceeded']);
                throw new RuntimeException('Envío pausado automáticamente: tasa de rebotes superior al 5%.');
            }

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

            $firstName = Sanitizer::html((string) ($item['first_name'] ?? ''));
            $subject   = str_replace('{{nombre}}', $firstName, $subject);
            $textBody  = str_replace('{{nombre}}', $firstName, $textBody);
            $htmlBody  = str_replace('{{nombre}}', $firstName, $htmlBody);

            $textFooter = "\n\n---\nSi ya no deseas recibir estos correos:\n" . $unsubscribeUrl;
            $htmlFooter = '<hr style="border:none;border-top:1px solid #eee;margin:20px 0"><p style="font-size:12px;color:#888;text-align:center">Si ya no deseas recibir estos correos, <a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '">cancela tu suscripción aquí</a>.</p>';

            if ($contentMode === 'html') {
                $htmlToSend = $htmlBody . $htmlFooter;
                $textToSend = $textBody !== '' ? $textBody . $textFooter : strip_tags($htmlBody) . $textFooter;
            } else {
                $textToSend = $textBody . $textFooter;
                $htmlToSend = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8')) . $htmlFooter;
            }

            $result = $ses->send($settings, [
                'to_email'          => $item['email'],
                'from_name'         => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
                'from_email'        => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
                'reply_to'          => $message['reply_to'] ?: ($settings['ses_reply_to'] ?? ''),
                'configuration_set' => $settings['ses_configuration_set'] ?? '',
                'subject'           => $subject,
                'html_body'         => $htmlToSend,
                'text_body'         => $textToSend,
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

    public function sendTest(int $campaignId, string $toEmail): array
    {
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campaña no encontrada.');
        }

        $message = (new CampaignMessage())->findByCampaign($campaignId);
        if (!$message) {
            throw new RuntimeException('La campaña no tiene mensaje configurado.');
        }

        $settings    = (new SettingsService())->all();
        $ses         = new SesV2Service();
        $contentMode = (string) ($campaign['content_mode'] ?? 'text');
        $textBody    = trim((string) ($message['text_body'] ?? ''));
        $htmlBody    = trim((string) ($message['html_body'] ?? ''));

        $testFooter     = "\n\n---\n[CORREO DE PRUEBA — No enviado a la lista real]";
        $testFooterHtml = '<hr><p style="font-size:12px;color:#e00;text-align:center"><strong>[CORREO DE PRUEBA — No enviado a la lista real]</strong></p>';

        if ($contentMode === 'html') {
            $htmlToSend = $htmlBody . $testFooterHtml;
            $textToSend = ($textBody !== '' ? $textBody : strip_tags($htmlBody)) . $testFooter;
        } else {
            $textToSend = $textBody . $testFooter;
            $htmlToSend = nl2br(htmlspecialchars($textBody, ENT_QUOTES, 'UTF-8')) . $testFooterHtml;
        }

        return $ses->send($settings, [
            'to_email'          => $toEmail,
            'from_name'         => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
            'from_email'        => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
            'reply_to'          => $message['reply_to'] ?: ($settings['ses_reply_to'] ?? ''),
            'configuration_set' => '',
            'subject'           => '[PRUEBA] ' . trim((string) $message['subject']),
            'html_body'         => $htmlToSend,
            'text_body'         => $textToSend,
        ]);
    }

    private function bounceRateExceeded(array $counts): bool
    {
        $processed = (int) ($counts['processed_count'] ?? 0);
        $failed    = (int) ($counts['failed_count'] ?? 0);

        if ($processed < 50) {
            return false;
        }

        return ($failed / $processed) > self::MAX_BOUNCE_RATE;
    }
}