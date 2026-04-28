<?php

declare(strict_types=1);

final class CampaignRecipientImportService
{
    public function import(int $campaignId, string $tmpPath, string $originalName, int $userId): array
    {
        if (!is_readable($tmpPath)) {
            throw new RuntimeException('No se pudo leer el archivo CSV.');
        }

        $handle = fopen($tmpPath, 'r');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new RuntimeException('El archivo CSV está vacío.');
        }

        $header = array_map(static fn ($value) => trim((string) $value), $header);
        $expected = ['correo', 'nombre', 'apellido', 'inst', 'pais', 'segmento', 'estado', 'consent'];

        if ($header !== $expected) {
            fclose($handle);
            throw new RuntimeException('La plantilla CSV no coincide. Debe usar: correo,nombre,apellido,inst,pais,segmento,estado,consent');
        }

        $recipientModel = new Recipient();
        $campaignRecipientModel = new CampaignRecipient();

        $recipientIds = [];
        $total = 0;
        $imported = 0;
        $failed = 0;

       while (($row = fgetcsv($handle)) !== false) {
        if (count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
            continue;
        }
        // Convertir encoding si es necesario
        $row = array_map(function($val) {
            if (!mb_detect_encoding($val, 'UTF-8', true)) {
                return mb_convert_encoding((string)$val, 'UTF-8', 'ISO-8859-1');
            }
            return (string)$val;
        }, $row);
        $total++;
        $data = [
        'email'       => Sanitizer::email((string) ($row[0] ?? '')),
        'first_name'  => Sanitizer::name((string) ($row[1] ?? '')),
        'last_name'   => Sanitizer::name((string) ($row[2] ?? '')),
        'institution' => Sanitizer::clean((string) ($row[3] ?? '')),
        'country'     => Sanitizer::clean((string) ($row[4] ?? '')),
        'segment'     => Sanitizer::clean((string) ($row[5] ?? '')),
        'status'      => $this->normalizeStatus((string) ($row[6] ?? '')),
        'consent_at'  => Sanitizer::clean((string) ($row[7] ?? '')),
    ];
            
            if (Sanitizer::isSuspicious($data['first_name']) || Sanitizer::isSuspicious($data['last_name'])) {
                $failed++;
                continue;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }

            try {
                $recipientId = $recipientModel->upsertAndGetId($data);
                $recipientIds[] = $recipientId;
                $imported++;
            } catch (Throwable $e) {
                $failed++;
            }
        }

        fclose($handle);

        $importId = $recipientModel->createImportRecordForCampaign($userId, $campaignId, $originalName, $total, $imported, $failed);

        foreach ($recipientIds as $recipientId) {
            $campaignRecipientModel->attach($campaignId, $recipientId, 'csv', $importId);
        }

        return [
            'total' => $total,
            'imported' => $imported,
            'failed' => $failed,
            'campaign_id' => $campaignId,
            'import_id' => $importId,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = mb_strtolower(trim($status));

        return match ($status) {
            'activo', 'active' => 'active',
            'inactivo', 'inactive' => 'inactive',
            'desuscrito', 'unsubscribed' => 'unsubscribed',
            default => 'active',
        };
    }
}