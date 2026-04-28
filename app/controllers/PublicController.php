<?php

declare(strict_types=1);

final class PublicController extends Controller
{
    public function unsubscribe(string $token): void
    {
        $recipientModel = new Recipient();
        $recipient = $recipientModel->findByUnsubscribeToken($token);
        if ($recipient) {
            $recipientModel->unsubscribeByToken($token);
        }
        $this->view('public/unsubscribe', [
            'title' => 'Desuscripción',
            'recipient' => $recipient,
        ]);
    }

    public function trackOpen(string $token): void
    {
        $tracker = new CampaignTrackingService();
        $tracker->registerOpen(
            $token,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        $pixel = base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==');

        if (!headers_sent()) {
            header('Content-Type: image/gif');
            header('Content-Length: ' . strlen((string) $pixel));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        echo $pixel;
        exit;
    }
}
