<?php

declare(strict_types=1);

final class Campaign extends Model
{
    public function all(): array
    {
        $sql = 'SELECT 
                    c.id,
                    c.name,
                    c.description,
                    COALESCE(NULLIF(c.status, ""), "draft") AS status,
                    c.created_by,
                    c.started_at,
                    c.finished_at,
                    c.created_at,
                    c.updated_at,
                    u.name AS creator_name,
                    (SELECT COUNT(*) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS sessions_count,
                    (SELECT COALESCE(MAX(ss.updated_at), c.updated_at) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS last_activity_at
                FROM campaigns c
                INNER JOIN users u ON u.id = c.created_by
                ORDER BY c.id DESC';

        return $this->fetchAll($sql);
    }


    public function searchPaginated(string $search = '', string $status = '', int $limit = 20, int $offset = 0): array
    {
        $limit = max(20, min(100, $limit));
        $offset = max(0, $offset);
        [$where, $params] = $this->searchFilters($search, $status);

        $sql = 'SELECT 
                    c.id,
                    c.name,
                    c.description,
                    COALESCE(NULLIF(c.status, ""), "draft") AS status,
                    c.created_by,
                    c.started_at,
                    c.finished_at,
                    c.created_at,
                    c.updated_at,
                    u.name AS creator_name,
                    (SELECT COUNT(*) FROM campaign_recipients cr WHERE cr.campaign_id = c.id AND cr.status = "active") AS recipients_count,
                    (SELECT COUNT(*) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS sessions_count,
                    (SELECT COALESCE(MAX(ss.updated_at), c.updated_at) FROM send_sessions ss WHERE ss.campaign_id = c.id) AS last_activity_at
                FROM campaigns c
                INNER JOIN users u ON u.id = c.created_by
                ' . $where . '
                ORDER BY c.id DESC
                LIMIT ' . $limit . ' OFFSET ' . $offset;

        return $this->fetchAll($sql, $params);
    }

    public function countSearch(string $search = '', string $status = ''): int
    {
        [$where, $params] = $this->searchFilters($search, $status);
        $row = $this->fetchOne('SELECT COUNT(*) AS total FROM campaigns c ' . $where, $params);
        return (int) ($row['total'] ?? 0);
    }

    private function searchFilters(string $search, string $status): array
    {
        $where = 'WHERE 1=1';
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $where .= ' AND (c.name LIKE :search OR c.description LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $allowedStatuses = ['draft', 'active', 'completed', 'cancelled'];
        if (in_array($status, $allowedStatuses, true)) {
            $where .= ' AND COALESCE(NULLIF(c.status, ""), "draft") = :status';
            $params['status'] = $status;
        }

        return [$where, $params];
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT 
                id,
                name,
                description,
                COALESCE(NULLIF(status, ""), "draft") AS status,
                created_by,
                started_at,
                finished_at,
                created_at,
                updated_at
             FROM campaigns
             WHERE id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO campaigns (name, description, status, created_by) VALUES (:name, :description, :status, :created_by)',
            [
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'status' => 'draft',
                'created_by' => $data['created_by'],
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateCampaign(int $id, array $data): void
    {
        $this->execute(
            'UPDATE campaigns SET name = :name, description = :description WHERE id = :id',
            [
                'id' => $id,
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
            ]
        );
    }

    public function activateCampaign(int $id): void
    {
        $this->execute(
            'UPDATE campaigns SET status = "active", finished_at = NULL WHERE id = :id',
            ['id' => $id]
        );
    }

    public function deactivateCampaign(int $id): void
    {
        $this->execute(
            'UPDATE campaigns SET status = "inactive" WHERE id = :id',
            ['id' => $id]
        );
    }

    public function deleteCampaign(int $id): void
    {
        $this->execute(
            'UPDATE campaigns SET status = "cancelled", finished_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
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
    
    public function updateSequenceSettings(int $id, array $data): void
{
    $this->execute(
        'UPDATE campaigns
         SET content_mode = :content_mode,
             sequence_start_at = :sequence_start_at,
             sequence_interval_days = :sequence_interval_days,
             sequence_total_steps = :sequence_total_steps
         WHERE id = :id',
        [
            'id' => $id,
            'content_mode' => $data['content_mode'],
            'sequence_start_at' => $data['sequence_start_at'],
            'sequence_interval_days' => $data['sequence_interval_days'],
            'sequence_total_steps' => $data['sequence_total_steps'],
        ]
    );
}
public function updateScheduleSettings(int $id, array $data): void
{
    $this->execute(
        'UPDATE campaigns
         SET content_mode = :content_mode,
             sequence_start_at = :sequence_start_at,
             sequence_interval_days = :sequence_interval_days,
             sequence_total_sends = :sequence_total_sends
         WHERE id = :id',
        [
            'id' => $id,
            'content_mode' => $data['content_mode'],
            'sequence_start_at' => $data['sequence_start_at'],
            'sequence_interval_days' => $data['sequence_interval_days'],
            'sequence_total_sends' => $data['sequence_total_sends'],
        ]
    );
}

public function updateContentMode(int $id, string $contentMode): void
{
    $this->execute(
        'UPDATE campaigns SET content_mode = :content_mode WHERE id = :id',
        [
            'id' => $id,
            'content_mode' => $contentMode,
        ]
    );
}


}