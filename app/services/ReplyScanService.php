<?php

declare(strict_types=1);

final class ReplyScanService
{
    public function scanAndStopSequences(int $limit = 200): array
    {
        $settings = (new SettingsService())->all();
        $enabled = (string) ($settings['reply_scan_enabled'] ?? '0');

        $result = [
            'enabled' => $enabled === '1',
            'ok' => true,
            'scanned' => 0,
            'matched' => 0,
            'stopped' => 0,
            'errors' => [],
        ];

        if ($enabled !== '1') {
            $result['ok'] = true;
            $result['errors'][] = 'Escaneo de respuestas desactivado.';
            return $result;
        }

        if (!function_exists('imap_open')) {
            $result['ok'] = false;
            $result['errors'][] = 'La extensión IMAP de PHP no está habilitada en el servidor.';
            return $result;
        }

        $host = trim((string) ($settings['reply_imap_host'] ?? 'imap.gmail.com'));
        $port = (int) ($settings['reply_imap_port'] ?? 993);
        $encryption = trim((string) ($settings['reply_imap_encryption'] ?? 'ssl'));
        $folder = trim((string) ($settings['reply_imap_folder'] ?? 'INBOX'));
        $username = trim((string) ($settings['reply_imap_username'] ?? ''));
        $password = (string) ($settings['reply_imap_password'] ?? '');
        $sinceDays = max(1, min(365, (int) ($settings['reply_scan_since_days'] ?? 30)));
        $limit = max(1, min(1000, $limit));

        if ($host === '' || $username === '' || $password === '') {
            $result['ok'] = false;
            $result['errors'][] = 'Faltan datos IMAP para leer respuestas.';
            return $result;
        }

        $flags = '/' . ($encryption === 'ssl' ? 'ssl' : 'notls') . '/novalidate-cert';
        $mailbox = '{' . $host . ':' . $port . '/imap' . $flags . '}' . $folder;

        $stream = @imap_open($mailbox, $username, $password, 0, 1);
        if ($stream === false) {
            $result['ok'] = false;
            $result['errors'][] = 'No se pudo conectar al buzón IMAP: ' . implode(' | ', imap_errors() ?: []);
            return $result;
        }

        try {
            $settingModel = new Setting();
            $lastUid = (int) ($settings['reply_scan_last_uid'] ?? 0);
            $since = date('d-M-Y', strtotime('-' . $sinceDays . ' days'));
            $uids = imap_search($stream, 'SINCE "' . $since . '"', SE_UID) ?: [];
            sort($uids, SORT_NUMERIC);
            $uids = array_values(array_filter($uids, static fn ($uid): bool => (int) $uid > $lastUid));
            $uids = array_slice($uids, 0, $limit);

            $maxUid = $lastUid;
            foreach ($uids as $uid) {
                $uid = (int) $uid;
                $maxUid = max($maxUid, $uid);
                $result['scanned']++;

                $header = imap_headerinfo($stream, imap_msgno($stream, $uid));
                if (!$header) {
                    continue;
                }

                $fromEmail = $this->firstAddress($header->from ?? []);
                if ($fromEmail === '') {
                    continue;
                }

                $target = $this->campaignRecipientFromHeader($header);
                $stopped = 0;
                if ($target !== null) {
                    $stopped = (new CampaignRecipient())->markResponded(
                        (int) $target['campaign_id'],
                        (int) $target['recipient_id'],
                        'Respuesta detectada automáticamente por IMAP desde ' . $fromEmail
                    );
                } else {
                    $stopped = (new CampaignRecipient())->markRespondedByEmail(
                        $fromEmail,
                        'Respuesta detectada automáticamente por IMAP.'
                    );
                }

                if ($stopped > 0) {
                    $result['matched']++;
                    $result['stopped'] += $stopped;
                }
            }

            if ($maxUid > $lastUid) {
                $settingModel->set('reply_scan_last_uid', (string) $maxUid, false);
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['errors'][] = $e->getMessage();
        } finally {
            imap_close($stream);
        }

        return $result;
    }

    private function campaignRecipientFromHeader(object $header): ?array
    {
        $automation = new ReplyAutomationService();
        $collections = [$header->to ?? [], $header->cc ?? []];
        foreach ($collections as $addresses) {
            foreach ((array) $addresses as $addr) {
                $email = $this->formatAddress($addr);
                $parsed = $automation->parseCampaignRecipientFromAddress($email);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }
        return null;
    }

    private function firstAddress(array $addresses): string
    {
        foreach ($addresses as $addr) {
            $email = $this->formatAddress($addr);
            if ($email !== '') {
                return $email;
            }
        }
        return '';
    }

    private function formatAddress(object $addr): string
    {
        $mailbox = strtolower(trim((string) ($addr->mailbox ?? '')));
        $host = strtolower(trim((string) ($addr->host ?? '')));
        if ($mailbox === '' || $host === '') {
            return '';
        }
        return $mailbox . '@' . $host;
    }
}
