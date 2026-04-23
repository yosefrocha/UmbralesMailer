<?php

declare(strict_types=1);

final class Campaign extends Model
{
    public function all(): array
    {
        $sql = 'SELECT c.*, u.name AS creator_name,
                (SELECT COUNT(*) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS sessions_count,
                (SELECT COALESCE(MAX(ss.updated_at), c.updated_at) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS last_activity_at
                FROM campaigns c
                INNER JOIN users u ON u.id = c.created_by
                ORDER BY c.id DESC';
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM campaigns WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data): int
    {
        $this->execute('INSERT INTO campaigns (name, description, status, created_by) VALUES (:name, :description, :status, :created_by)', [
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'status' => 'draft',
            'created_by' => $data['created_by'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateCampaign(int $id, array $data): void
    {
        $this->execute('UPDATE campaigns SET name = :name, description = :description WHERE id = :id', [
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);
    }

    public function deleteCampaign(int $id): void
    {
        $this->execute('UPDATE campaigns SET status = "cancelled" WHERE id = :id', ['id' => $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $fields = ['status' => $status, 'id' => $id];
        $sql = 'UPDATE campaigns SET status = :status';
        if ($status === 'processing') {
            $sql .= ', started_at = IFNULL(started_at, NOW())';
        }
        if (in_array($status, ['completed', 'failed', 'cancelled'], true)) {
            $sql .= ', finished_at = NOW()';
        }
        $sql .= ' WHERE id = :id';
        $this->execute($sql, $fields);
    }

    public function stats(int $id): array
    {
        $campaign = $this->find($id);
        $messageModel = new CampaignMessage();
        $message = $messageModel->findByCampaign((int) $id);
        $sessionModel = new SendSession();
        $latest = $sessionModel->latestByCampaign((int) $id);
        return [
            'campaign' => $campaign,
            'message' => $message,
            'latest_session' => $latest,
        ];
    }
}
