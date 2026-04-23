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
            $total++;
            $assoc = [];
            foreach ($header as $index => $column) {
                $assoc[$column] = trim((string) ($row[$index] ?? ''));
            }

            $email = $assoc['email'] ?? '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                $errors[] = 'Fila ' . ($total + 1) . ': correo inválido';
                continue;
            }

            try {
                $recipientModel->upsert([
                    'email' => $email,
                    'first_name' => $assoc['first_name'] ?? ($assoc['firstname'] ?? ''),
                    'last_name' => $assoc['last_name'] ?? ($assoc['lastname'] ?? ''),
                    'institution' => $assoc['institution'] ?? '',
                    'country' => $assoc['country'] ?? '',
                    'segment' => $assoc['segment'] ?? '',
                    'status' => $assoc['status'] ?? 'active',
                    'consent_at' => $assoc['consent_at'] ?? '',
                ]);
                $imported++;
            } catch (Throwable $e) {
                $failed++;
                $errors[] = 'Fila ' . ($total + 1) . ': ' . $e->getMessage();
            }
        }
        fclose($handle);

        $recipientModel->createImportRecord($userId, $originalName, $total, $imported, $failed);
        return compact('total', 'imported', 'failed', 'errors');
    }
}
