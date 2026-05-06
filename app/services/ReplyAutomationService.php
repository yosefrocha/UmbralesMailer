<?php

declare(strict_types=1);

final class ReplyAutomationService
{
    public function replyToForRecipient(string $baseReplyTo, int $campaignId, int $recipientId): string
    {
        $baseReplyTo = trim($baseReplyTo);
        if ($baseReplyTo === '' || !filter_var($baseReplyTo, FILTER_VALIDATE_EMAIL)) {
            return $baseReplyTo;
        }

        [$local, $domain] = explode('@', $baseReplyTo, 2);
        if ($local === '' || $domain === '') {
            return $baseReplyTo;
        }

        // Alias compatible con Gmail / Google Workspace: usuario+umc33r15@dominio.com
        // Si el buzón no acepta alias con '+', el escaneo también puede detener por From.
        if (str_contains($local, '+umc')) {
            $local = preg_replace('/\+umc\d+r\d+$/', '', $local) ?? $local;
        }

        return $local . '+umc' . $campaignId . 'r' . $recipientId . '@' . $domain;
    }

    public function parseCampaignRecipientFromAddress(string $address): ?array
    {
        $address = strtolower($address);
        if (preg_match('/\+umc(\d+)r(\d+)@/i', $address, $m) === 1) {
            return [
                'campaign_id' => (int) $m[1],
                'recipient_id' => (int) $m[2],
            ];
        }
        return null;
    }
}
