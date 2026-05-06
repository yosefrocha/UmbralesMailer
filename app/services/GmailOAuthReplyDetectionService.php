<?php

declare(strict_types=1);

/**
 * Preparacion para deteccion segura de respuestas con Gmail API / OAuth.
 *
 * Esta clase queda intencionalmente inactiva. No usa IMAP, no guarda
 * contrasenas de buzon y no se ejecuta desde el cron hasta completar
 * la fase OAuth con Google Cloud.
 */
final class GmailOAuthReplyDetectionService
{
    public function isConfigured(array $settings): bool
    {
        return (string) ($settings['reply_detection_provider'] ?? '') === 'gmail_oauth'
            && (string) ($settings['reply_detection_enabled'] ?? '0') === '1'
            && trim((string) ($settings['gmail_oauth_client_id'] ?? '')) !== ''
            && trim((string) ($settings['gmail_oauth_client_secret'] ?? '')) !== ''
            && trim((string) ($settings['gmail_oauth_refresh_token'] ?? '')) !== ''
            && trim((string) ($settings['gmail_oauth_mailbox'] ?? '')) !== '';
    }

    public function scan(): array
    {
        return [
            'ok' => false,
            'status' => 'pendiente',
            'message' => 'Gmail API / OAuth queda preparado para una fase posterior. No hay escaneo activo todavia.',
            'matched_replies' => 0,
            'stopped_sequences' => 0,
        ];
    }
}
