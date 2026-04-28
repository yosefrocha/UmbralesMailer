<?php

declare(strict_types=1);

final class Recipient extends Model
{
    public function all(string $search = '', string $status = '', string $segment = '', string $country = ''): array
    {
        $sql = 'SELECT * FROM recipients WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $term = '%' . $search . '%';
            $sql .= ' AND (email LIKE :term_email OR first_name LIKE :term_first_name OR last_name LIKE :term_last_name OR institution LIKE :term_institution OR segment LIKE :term_segment)';
            $params['term_email'] = $term;
            $params['term_first_name'] = $term;
            $params['term_last_name'] = $term;
            $params['term_institution'] = $term;
            $params['term_segment'] = $term;
        }

        if ($status !== '') {
            if ($status === 'subscribed') {
                $sql .= ' AND unsubscribed_at IS NULL';
            } elseif ($status === 'unsubscribed') {
                $sql .= ' AND unsubscribed_at IS NOT NULL';
            } else {
                $sql .= ' AND status = :status';
                $params['status'] = $status;
            }
        }

        if ($segment !== '') {
            $sql .= ' AND segment = :segment';
            $params['segment'] = $segment;
        }

        if ($country !== '') {
            $sql .= ' AND country = :country';
            $params['country'] = $country;
        }

        $sql .= ' ORDER BY id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM recipients WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne('SELECT * FROM recipients WHERE email = :email LIMIT 1', ['email' => $email]);
    }

    public function countActive(): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM recipients WHERE unsubscribed_at IS NULL AND status = "active"');
        return (int) ($row['total'] ?? 0);
    }

    public function getSegments(): array
    {
        $rows = $this->fetchAll('SELECT DISTINCT segment FROM recipients WHERE segment IS NOT NULL AND segment != "" ORDER BY segment');
        return array_column($rows, 'segment');
    }

    public function getCountries(): array
    {
        $rows = $this->fetchAll('SELECT DISTINCT country FROM recipients WHERE country IS NOT NULL AND country != "" ORDER BY country');
        return array_column($rows, 'country');
    }

    /**
     * Valida los datos de un destinatario antes de guardar
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // Email obligatorio y válido
        if (empty($data['email'])) {
            $errors[] = 'El correo es obligatorio.';
        } elseif (!Sanitizer::isValidEmail($data['email'])) {
            $errors[] = 'El correo "' . Sanitizer::html($data['email']) . '" no es válido.';
        }

        // Nombre: solo letras, espacios, guiones, caracteres latinos
        if (!empty($data['first_name']) && !Sanitizer::isValidName($data['first_name'])) {
            $errors[] = 'El nombre "' . Sanitizer::html($data['first_name']) . '" contiene caracteres no permitidos.';
        }

        if (!empty($data['last_name']) && !Sanitizer::isValidName($data['last_name'])) {
            $errors[] = 'El apellido "' . Sanitizer::html($data['last_name']) . '" contiene caracteres no permitidos.';
        }

        // Detectar inyección
        foreach ($data as $key => $value) {
            if (is_string($value) && Sanitizer::isSuspicious($value)) {
                $errors[] = "El campo {$key} contiene contenido no permitido.";
            }
        }

        return $errors;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO recipients (email, first_name, last_name, institution, country, segment, status, consent_at)
                VALUES (:email, :first_name, :last_name, :institution, :country, :segment, :status, :consent_at)';

        $this->execute($sql, [
            'email' => Sanitizer::email($data['email']),
            'first_name' => !empty($data['first_name']) ? Sanitizer::name((string)$data['first_name']) : null,
            'last_name' => !empty($data['last_name']) ? Sanitizer::name((string)$data['last_name']) : null,
            'institution' => !empty($data['institution']) ? Sanitizer::clean((string)$data['institution']) : null,
            'country' => !empty($data['country']) ? Sanitizer::clean((string)$data['country']) : null,
            'segment' => !empty($data['segment']) ? Sanitizer::clean((string)$data['segment']) : null,
            'status' => $data['status'] ?: 'active',
            'consent_at' => $data['consent_at'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateRecipient(int $id, array $data): void
    {
        $sql = 'UPDATE recipients SET email = :email, first_name = :first_name, last_name = :last_name,
                institution = :institution, country = :country, segment = :segment, status = :status, consent_at = :consent_at
                WHERE id = :id';

        $this->execute($sql, [
            'id' => $id,
            'email' => Sanitizer::email($data['email']),
            'first_name' => !empty($data['first_name']) ? Sanitizer::name((string)$data['first_name']) : null,
            'last_name' => !empty($data['last_name']) ? Sanitizer::name((string)$data['last_name']) : null,
            'institution' => !empty($data['institution']) ? Sanitizer::clean((string)$data['institution']) : null,
            'country' => !empty($data['country']) ? Sanitizer::clean((string)$data['country']) : null,
            'segment' => !empty($data['segment']) ? Sanitizer::clean((string)$data['segment']) : null,
            'status' => $data['status'] ?: 'active',
            'consent_at' => $data['consent_at'] ?: null,
        ]);
    }

    public function upsert(array $data): void
    {
        $existing = $this->findByEmail(Sanitizer::email($data['email']));
        if ($existing) {
            $this->updateRecipient((int) $existing['id'], $data);
            return;
        }
        $this->create($data);
    }

    public function upsertAndGetId(array $data): int
    {
        $existing = $this->findByEmail(Sanitizer::email($data['email']));
        if ($existing) {
            $this->updateRecipient((int) $existing['id'], $data);
            return (int) $existing['id'];
        }
        return $this->create($data);
    }

    public function generateOrGetToken(int $recipientId): string
    {
        $row = $this->fetchOne('SELECT token FROM unsubscribe_tokens WHERE recipient_id = :recipient_id LIMIT 1', ['recipient_id' => $recipientId]);
        if ($row) {
            return $row['token'];
        }
        $token = bin2hex(random_bytes(32));
        $this->execute('INSERT INTO unsubscribe_tokens (recipient_id, token) VALUES (:recipient_id, :token)', ['recipient_id' => $recipientId, 'token' => $token]);
        return $token;
    }

    public function findByUnsubscribeToken(string $token): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, ut.id AS token_id, ut.used_at FROM unsubscribe_tokens ut
             INNER JOIN recipients r ON r.id = ut.recipient_id WHERE ut.token = :token LIMIT 1',
            ['token' => $token]
        );
    }

    public function unsubscribeByToken(string $token): void
    {
        $this->execute(
            'UPDATE recipients r INNER JOIN unsubscribe_tokens ut ON ut.recipient_id = r.id
             SET r.unsubscribed_at = NOW(), r.status = "unsubscribed", ut.used_at = IFNULL(ut.used_at, NOW())
             WHERE ut.token = :token',
            ['token' => $token]
        );
    }

    public function createImportRecordForCampaign(int $userId, ?int $campaignId, string $filename, int $total, int $imported, int $failed): int
    {
        $this->execute(
            'INSERT INTO recipient_imports (uploaded_by, campaign_id, original_filename, total_rows, imported_rows, failed_rows)
             VALUES (:uploaded_by, :campaign_id, :filename, :total_rows, :imported_rows, :failed_rows)',
            ['uploaded_by' => $userId, 'campaign_id' => $campaignId, 'filename' => $filename, 'total_rows' => $total, 'imported_rows' => $imported, 'failed_rows' => $failed]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Destinatarios disponibles para asignar a una campaña (que no estén ya asignados)
     */
    public function availableForCampaign(int $campaignId, string $search = '', string $segment = '', string $country = ''): array
    {
        $sql = 'SELECT r.* FROM recipients r
                WHERE r.status = "active" AND r.unsubscribed_at IS NULL
                AND r.id NOT IN (SELECT recipient_id FROM campaign_recipients WHERE campaign_id = :campaign_id)';
        $params = ['campaign_id' => $campaignId];

        if ($search !== '') {
            $term = '%' . $search . '%';
            $sql .= ' AND (r.email LIKE :term OR r.first_name LIKE :term2 OR r.last_name LIKE :term3 OR r.institution LIKE :term4)';
            $params['term'] = $term;
            $params['term2'] = $term;
            $params['term3'] = $term;
            $params['term4'] = $term;
        }

        if ($segment !== '') {
            $sql .= ' AND r.segment = :segment';
            $params['segment'] = $segment;
        }

        if ($country !== '') {
            $sql .= ' AND r.country = :country';
            $params['country'] = $country;
        }

        $sql .= ' ORDER BY r.id DESC LIMIT 500';

        return $this->fetchAll($sql, $params);
    }

    /**
     * Historial de campañas de un destinatario
     */
    public function campaignHistory(int $recipientId): array
    {
        return $this->fetchAll(
            'SELECT c.name AS campaign_name, c.id AS campaign_id, cr.status AS cr_status, cr.assigned_at,
                    ssi.status AS send_status, ssi.processed_at, ssi.error_message
             FROM campaign_recipients cr
             INNER JOIN campaigns c ON c.id = cr.campaign_id
             LEFT JOIN send_session_items ssi ON ssi.recipient_id = cr.recipient_id
             WHERE cr.recipient_id = :recipient_id
             ORDER BY cr.assigned_at DESC',
            ['recipient_id' => $recipientId]
        );
    }
}
