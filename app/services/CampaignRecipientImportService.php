<?php

declare(strict_types=1);

final class CampaignRecipientImportService
{
    private const EXPECTED_HEADER = ['correo', 'nombre', 'apellido', 'inst', 'pais', 'segmento', 'estado', 'consent'];

    public function validate(string $tmpPath): array
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

        $header = $this->normalizeHeader($header);
        $errors = [];
        $valid = 0;
        $total = 0;
        $duplicates = 0;
        $line = 1;
        $seenEmails = [];

        if ($header !== self::EXPECTED_HEADER) {
            fclose($handle);
            return [
                'total' => 0,
                'valid' => 0,
                'duplicates' => 0,
                'errors' => ['La cabecera del CSV no es correcta. Debe usar: correo,nombre,apellido,inst,pais,segmento,estado,consent'],
            ];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $total++;
            $row = $this->normalizeRowEncoding($row);
            $data = $this->mapRow($row);
            $email = $data['email'];

            if ($email !== '' && isset($seenEmails[$email])) {
                $duplicates++;
                $errors[] = "Fila {$line}: el correo {$email} está duplicado dentro del CSV. Primera aparición en fila {$seenEmails[$email]}.";
                continue;
            }
            if ($email !== '') {
                $seenEmails[$email] = $line;
            }

            $rowErrors = Recipient::validate($data);
            if (!in_array($data['status'], ['active', 'inactive', 'unsubscribed'], true)) {
                $rowErrors[] = 'El estado debe ser activo, inactivo o desuscrito.';
            }

            if (!empty($rowErrors)) {
                foreach ($rowErrors as $error) {
                    $errors[] = "Fila {$line}: {$error}";
                }
                continue;
            }

            $valid++;
        }

        fclose($handle);

        return [
            'total' => $total,
            'valid' => $valid,
            'duplicates' => $duplicates,
            'errors' => $errors,
        ];
    }

    public function import(int $campaignId, string $tmpPath, string $originalName, int $userId): array
    {
        $validation = $this->validate($tmpPath);
        if (!empty($validation['errors'])) {
            throw new RuntimeException('El CSV contiene errores. Valide el archivo antes de importar.');
        }

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

        $header = $this->normalizeHeader($header);
        if ($header !== self::EXPECTED_HEADER) {
            fclose($handle);
            throw new RuntimeException('La plantilla CSV no coincide. Debe usar: correo,nombre,apellido,inst,pais,segmento,estado,consent');
        }

        $recipientModel = new Recipient();
        $campaignRecipientModel = new CampaignRecipient();

        $recipientIds = [];
        $seenEmails = [];
        $total = 0;
        $imported = 0;
        $updated = 0;
        $created = 0;
        $failed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = $this->normalizeRowEncoding($row);
            $data = $this->mapRow($row);
            $email = $data['email'];

            if ($email === '' || isset($seenEmails[$email])) {
                continue;
            }
            $seenEmails[$email] = true;
            $total++;

            try {
                $existing = $recipientModel->findByEmail($email);
                $recipientId = $recipientModel->upsertAndGetId($data);
                $recipientIds[] = $recipientId;
                $imported++;
                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            } catch (Throwable $e) {
                $failed++;
            }
        }

        fclose($handle);

        $importId = $recipientModel->createImportRecordForCampaign($userId, $campaignId, $originalName, $total, $imported, $failed);

        foreach (array_unique($recipientIds) as $recipientId) {
            $campaignRecipientModel->attach($campaignId, (int) $recipientId, 'csv', $importId);
        }

        return [
            'total' => $total,
            'imported' => $imported,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'campaign_id' => $campaignId,
            'import_id' => $importId,
        ];
    }

    private function normalizeHeader(array $header): array
    {
        return array_map(
            static fn ($value) => mb_strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value))),
            $header
        );
    }

    private function normalizeRowEncoding(array $row): array
    {
        return array_map(function ($value): string {
            $value = (string) $value;
            if (!mb_detect_encoding($value, 'UTF-8', true)) {
                return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
            }
            return $value;
        }, $row);
    }

    private function mapRow(array $row): array
    {
        return [
            'email'       => Sanitizer::email((string) ($row[0] ?? '')),
            'first_name'  => Sanitizer::name((string) ($row[1] ?? '')),
            'last_name'   => Sanitizer::name((string) ($row[2] ?? '')),
            'institution' => Sanitizer::clean((string) ($row[3] ?? '')),
            'country'     => Sanitizer::clean((string) ($row[4] ?? '')),
            'segment'     => Sanitizer::clean((string) ($row[5] ?? '')),
            'status'      => $this->normalizeStatus((string) ($row[6] ?? '')),
            'consent_at'  => Sanitizer::clean((string) ($row[7] ?? '')),
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
