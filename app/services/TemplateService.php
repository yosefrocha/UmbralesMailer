<?php

declare(strict_types=1);

final class TemplateService
{
    public function render(string $content, array $recipient, string $unsubscribeUrl): string
    {
        $replacements = [
            '%%firstName%%' => $recipient['first_name'] ?? '',
            '%%lastName%%' => $recipient['last_name'] ?? '',
            '%%emailAddress%%' => $recipient['email'] ?? '',
            '%%institution%%' => $recipient['institution'] ?? '',
            '%%country%%' => $recipient['country'] ?? '',
            '%%segment%%' => $recipient['segment'] ?? '',
            '%%status%%' => $recipient['recipient_status'] ?? ($recipient['status'] ?? ''),
            '%%consentDate%%' => $recipient['consent_at'] ?? '',
            '%%unsubscribeUrl%%' => $unsubscribeUrl,
        ];

        return strtr($content, $replacements);
    }
}
