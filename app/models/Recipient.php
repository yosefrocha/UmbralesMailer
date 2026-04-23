<?php

declare(strict_types=1);

final class Recipient extends Model
{
    public function all(string $search = '', string $status = ''): array
    {
        $sql = 'SELECT * FROM recipients WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $term = '%' . $search . '%';
            $sql .= ' AND (
                email LIKE :term_email
                OR first_name LIKE :term_first_name
                OR last_name LIKE :term_last_name
                OR institution LIKE :term_institution
                OR segment LIKE :term_segment
            )';

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

        $sql .= ' ORDER BY id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM recipients WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM recipients WHERE email = :email LIMIT 1',
            ['email' => $email]
        );
    }

    public function countActive(): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total FROM recipients WHERE unsubscribed_at IS NULL AND status = "active"'
        );

        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO recipients (email, first_name, last_name, institution, country, segment, status, consent_at)
                VALUES (:email, :first_name, :last_name, :institution, :country, :segment, :status, :consent_at)';

        $this->execute($sql, [
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?: null,
            'last_name' => $data['last_name'] ?: null,
            'institution' => $data['institution'] ?: null,
            'country' => $data['country'] ?: null,
            'segment' => $data['segment'] ?: null,
            'status' => $data['status'] ?: 'active',
            'consent_at' => $data['consent_at'] ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateRecipient(int $id, array $data): void
    {
        $sql = 'UPDATE recipients
                SET email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    institution = :institution,
                    country = :country,
                    segment = :segment,
                    status = :status,
                    consent_at = :consent_at
                WHERE id = :id';

        $this->execute($sql, [
            'id' => $id,
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?: null,
            'last_name' => $data['last_name'] ?: null,
            'institution' => $data['institution'] ?: null,
            'country' => $data['country'] ?: null,
            'segment' => $data['segment'] ?: null,
            'status' => $data['status'] ?: 'active',
            'consent_at' => $data['consent_at'] ?: null,
        ]);
    }

    public function upsert(array $data): void
    {
        $existing = $this->fetchOne(
            'SELECT id FROM recipients WHERE email = :email LIMIT 1',
            ['email' => $data['email']]
        );

        if ($existing) {
            $this->updateRecipient((int) $existing['id'], $data);
            return;
        }

        $this->create($data);
    }

    public function upsertAndGetId(array $data): int
    {
        $existing = $this->findByEmail($data['email']);

        if ($existing) {
            $this->updateRecipient((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        return $this->create($data);
    }

    public function generateOrGetToken(int $recipientId): string
    {
        $row = $this->fetchOne(
            'SELECT token FROM unsubscribe_tokens WHERE recipient_id = :recipient_id LIMIT 1',
            ['recipient_id' => $recipientId]
        );

        if ($row) {
            return $row['token'];
        }

        $token = bin2hex(random_bytes(32));

        $this->execute(
            'INSERT INTO unsubscribe_tokens (recipient_id, token) VALUES (:recipient_id, :token)',
            [
                'recipient_id' => $recipientId,
                'token' => $token,
            ]
        );

        return $token;
    }

    public function findByUnsubscribeToken(string $token): ?array
    {
        return $this->fetchOne(
            'SELECT r.*, ut.id AS token_id, ut.used_at
             FROM unsubscribe_tokens ut
             INNER JOIN recipients r ON r.id = ut.recipient_id
             WHERE ut.token = :token
             LIMIT 1',
            ['token' => $token]
        );
    }

    public function unsubscribeByToken(string $token): void
    {
        $this->execute(
            'UPDATE recipients r
             INNER JOIN unsubscribe_tokens ut ON ut.recipient_id = r.id
             SET r.unsubscribed_at = NOW(),
                 r.status = "unsubscribed",
                 ut.used_at = IFNULL(ut.used_at, NOW())
             WHERE ut.token = :token',
            ['token' => $token]
        );
    }

    public function createImportRecord(int $userId, string $filename, int $total, int $imported, int $failed): void
    {
        $this->execute(
            'INSERT INTO recipient_imports (uploaded_by, original_filename, total_rows, imported_rows, failed_rows)
             VALUES (:uploaded_by, :filename, :total_rows, :imported_rows, :failed_rows)',
            [
                'uploaded_by' => $userId,
                'filename' => $filename,
                'total_rows' => $total,
                'imported_rows' => $imported,
                'failed_rows' => $failed,
            ]
        );
    }

    public function createImportRecordForCampaign(int $userId, ?int $campaignId, string $filename, int $total, int $imported, int $failed): int
    {
        $this->execute(
            'INSERT INTO recipient_imports (uploaded_by, campaign_id, original_filename, total_rows, imported_rows, failed_rows)
             VALUES (:uploaded_by, :campaign_id, :filename, :total_rows, :imported_rows, :failed_rows)',
            [
                'uploaded_by' => $userId,
                'campaign_id' => $campaignId,
                'filename' => $filename,
                'total_rows' => $total,
                'imported_rows' => $imported,
                'failed_rows' => $failed,
            ]
        );

        return (int) $this->db->lastInsertId();
    }
}