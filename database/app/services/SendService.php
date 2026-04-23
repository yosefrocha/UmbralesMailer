<?php

declare(strict_types=1);

final class SendService
{
    public function start(int $campaignId): int
    {
        $campaignModel = new Campaign();
        $messageModel = new CampaignMessage();
        $sessionModel = new SendSession();

        $campaign = $campaignModel->find($campaignId);
        if (!$campaign) {
            throw new RuntimeException('Campaña no encontrada.');
        }

        $message = $messageModel->findByCampaign($campaignId);
        if (!$message) {
            throw new RuntimeException('La campaña no tiene mensaje configurado.');
        }

        $sessionId = $sessionModel->create($campaignId, (int) $message['id']);
        $sessionModel->addItemsFromActiveRecipients($sessionId);
        $sessionModel->setStatus($sessionId, 'processing');
        $campaignModel->setStatus($campaignId, 'processing');
        AuditLogger::log('send.started', 'campaign', $campaignId, ['send_session_id' => $sessionId]);
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

        $campaignMessageModel = new CampaignMessage();
        $message = $campaignMessageModel->findByCampaign((int) $session['campaign_id']);
        if (!$message) {
            throw new RuntimeException('Mensaje de campaña no encontrado.');
        }

        $settings = (new SettingsService())->all();
        $recipientModel = new Recipient();
        $template = new TemplateService();
        $ses = new SesV2Service();
        $batch = $sessionModel->getPendingItems($sessionId, $limit);
        $appConfig = require CONFIG_PATH . '/app.php';
        $baseUrl = rtrim((string) ($appConfig['base_url'] ?? ''), '/');

        foreach ($batch as $item) {
            $token = $recipientModel->generateOrGetToken((int) $item['recipient_id']);
            $unsubscribeUrl = $baseUrl . (($appConfig['unsubscribe_path'] ?? '/unsubscribe/') . $token);
            $subject = $template->render((string) $message['subject'], $item, $unsubscribeUrl);
            $htmlBody = $template->render((string) $message['html_body'], $item, $unsubscribeUrl);
            $textBody = $template->render((string) $message['text_body'], $item, $unsubscribeUrl);

            $result = $ses->send($settings, [
                'to_email' => $item['email'],
                'from_name' => $message['from_name'] ?: ($settings['ses_from_name'] ?? 'Equipo Umbrales'),
                'from_email' => $message['from_email'] ?: ($settings['ses_from_email'] ?? ''),
                'reply_to' => $message['reply_to'] ?: ($settings['ses_reply_to'] ?? ''),
                'configuration_set' => $settings['ses_configuration_set'] ?? '',
                'subject' => $subject,
                'html_body' => $htmlBody,
                'text_body' => $textBody,
            ]);

            if ($result['ok'] ?? false) {
                $sessionModel->markItemSent((int) $item['id'], (string) ($result['message_id'] ?? ''));
            } else {
                $sessionModel->markItemFailed((int) $item['id'], (string) ($result['error'] ?? 'Error desconocido'));
            }
        }

        $updated = $sessionModel->refreshCounts($sessionId);
        if (($updated['status'] ?? '') === 'completed') {
            (new Campaign())->setStatus((int) $session['campaign_id'], 'completed');
        }
        return $updated;
    }
}
