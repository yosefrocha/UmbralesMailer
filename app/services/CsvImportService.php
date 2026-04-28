<?php
declare(strict_types=1);
final class CsvImportService
{
    public function import(string $filePath, string $originalName, int $userId): array
    {
        $recipientModel = new Recipient();
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo CSV.');
        }
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new RuntimeException('El CSV está vacío.');
        }
        $header = array_map(static fn($item) => strtolower(trim((string) $item)), $header);
        $total = 0;
        $imported = 0;
        $failed = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            // Convertir encoding si es necesario
            $row = array_map(function($val) {
                if (!mb_detect_encoding((string)$val, 'UTF-8', true)) {
                    return mb_convert_encoding((string)$val, 'UTF-8', 'ISO-8859-1');
                }
                return (string)$val;
            }, $row);

            $total++;

            $assoc = [];
            foreach ($header as $index => $column) {
                $assoc[$column] = (string) ($row[$index] ?? '');
            }

            $email = Sanitizer::email($assoc['correo'] ?? $assoc['email'] ?? '');
            if (!Sanitizer::isValidEmail($email)) {
                $failed++;
                $errors[] = 'Fila ' . ($total + 1) . ': correo inválido';
                continue;
            }

            $data = [
                'email'       => $email,
                'first_name'  => Sanitizer::name($assoc['nombre'] ?? $assoc['first_name'] ?? ''),
                'last_name'   => Sanitizer::name($assoc['apellido'] ?? $assoc['last_name'] ?? ''),
                'institution' => Sanitizer::clean($assoc['inst'] ?? $assoc['institution'] ?? ''),
                'country'     => Sanitizer::clean($assoc['pais'] ?? $assoc['country'] ?? ''),
                'segment'     => Sanitizer::clean($assoc['segmento'] ?? $assoc['segment'] ?? ''),
                'status'      => $this->normalizeStatus($assoc['estado'] ?? $assoc['status'] ?? ''),
                'consent_at'  => Sanitizer::clean($assoc['consent'] ?? $assoc['consent_at'] ?? ''),
            ];

            if (Sanitizer::isSuspicious($data['first_name']) || Sanitizer::isSuspicious($data['last_name'])) {
                $failed++;
                $errors[] = 'Fila ' . ($total + 1) . ': nombre contiene caracteres no permitidos';
                continue;
            }

            try {
                $recipientModel->upsertAndGetId($data);
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = 'Fila ' . ($total + 1) . ': ' . $e->getMessage();
            }
        }

        fclose($handle);
        $recipientModel->createImportRecordForCampaign($userId, null, $originalName, $total, $imported, $failed);

        return compact('total', 'imported', 'failed', 'errors');
    }

    private function normalizeStatus(string $status): string
    {
        $status = mb_strtolower(trim($status));
        return match ($status) {
            'activo', 'active'           => 'active',
            'inactivo', 'inactive'       => 'inactive',
            'desuscrito', 'unsubscribed' => 'unsubscribed',
            default                      => 'active',
        };
    }
}