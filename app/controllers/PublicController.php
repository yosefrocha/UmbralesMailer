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
}
