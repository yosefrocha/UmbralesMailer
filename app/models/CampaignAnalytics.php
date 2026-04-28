<?php

declare(strict_types=1);

final class CampaignAnalytics extends Model
{
    public function dashboardTotals(): array
    {
        return [
            'campaigns' => $this->safeScalar('SELECT COUNT(*) FROM campaigns'),
            'active_recipients' => $this->safeScalar('SELECT COUNT(*) FROM recipients WHERE status = "active" AND unsubscribed_at IS NULL'),
            'sent' => $this->safeScalar('SELECT COUNT(*) FROM send_session_items WHERE status = "sent"'),
            'opened' => $this->countUniqueOpens(),
            'open_events' => $this->countTotalOpenEvents(),
            'bounced' => $this->safeScalar('SELECT COUNT(*) FROM send_session_items WHERE status = "failed"'),
            'unsubscribed' => $this->safeScalar('SELECT COUNT(*) FROM recipients WHERE unsubscribed_at IS NOT NULL'),
        ];
    }

    public function campaignPerformance(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        $hasOpenColumns = $this->hasColumn('send_session_items', 'opened_at') && $this->hasColumn('send_session_items', 'open_count');

        if ($hasOpenColumns) {
            $openUniqueSql = '(SELECT COUNT(DISTINCT ssi_o.recipient_id)
                              FROM send_sessions ss_o
                              INNER JOIN send_session_items ssi_o ON ssi_o.send_session_id = ss_o.id
                              WHERE ss_o.campaign_id = c.id
                                AND ssi_o.opened_at IS NOT NULL)';
            $openEventsSql = '(SELECT COALESCE(SUM(ssi_oe.open_count), 0)
                              FROM send_sessions ss_oe
                              INNER JOIN send_session_items ssi_oe ON ssi_oe.send_session_id = ss_oe.id
                              WHERE ss_oe.campaign_id = c.id
                                AND ssi_oe.opened_at IS NOT NULL)';
        } else {
            $openUniqueSql = '0';
            $openEventsSql = '0';
        }

        $sql = 'SELECT
                    c.id,
                    c.name,
                    COALESCE(NULLIF(c.status, ""), "draft") AS status,
                    (SELECT COUNT(*)
                     FROM campaign_recipients cr_a
                     WHERE cr_a.campaign_id = c.id AND cr_a.status = "active") AS assigned_count,
                    (SELECT COUNT(*)
                     FROM send_sessions ss_t
                     INNER JOIN send_session_items ssi_t ON ssi_t.send_session_id = ss_t.id
                     WHERE ss_t.campaign_id = c.id) AS total_items,
                    (SELECT COUNT(*)
                     FROM send_sessions ss_s
                     INNER JOIN send_session_items ssi_s ON ssi_s.send_session_id = ss_s.id
                     WHERE ss_s.campaign_id = c.id AND ssi_s.status = "sent") AS sent_count,
                    (SELECT COUNT(*)
                     FROM send_sessions ss_f
                     INNER JOIN send_session_items ssi_f ON ssi_f.send_session_id = ss_f.id
                     WHERE ss_f.campaign_id = c.id AND ssi_f.status = "failed") AS bounced_count,
                    (SELECT COUNT(*)
                     FROM send_sessions ss_p
                     INNER JOIN send_session_items ssi_p ON ssi_p.send_session_id = ss_p.id
                     WHERE ss_p.campaign_id = c.id AND ssi_p.status IN ("sent", "failed", "skipped")) AS processed_count,
                    ' . $openUniqueSql . ' AS opened_count,
                    ' . $openEventsSql . ' AS open_events_count,
                    (SELECT COUNT(DISTINCT cr_u.recipient_id)
                     FROM campaign_recipients cr_u
                     INNER JOIN recipients r_u ON r_u.id = cr_u.recipient_id
                     WHERE cr_u.campaign_id = c.id
                       AND cr_u.status = "active"
                       AND r_u.unsubscribed_at IS NOT NULL) AS unsubscribed_count,
                    COALESCE((SELECT MAX(ss_l.updated_at) FROM send_sessions ss_l WHERE ss_l.campaign_id = c.id), c.updated_at) AS last_activity_at
                FROM campaigns c
                ORDER BY last_activity_at DESC, c.id DESC
                LIMIT ' . $limit;

        $rows = $this->safeFetchAll($sql);

        foreach ($rows as &$row) {
            $row['assigned_count'] = (int) ($row['assigned_count'] ?? 0);
            $row['total_items'] = (int) ($row['total_items'] ?? 0);
            $row['sent_count'] = (int) ($row['sent_count'] ?? 0);
            $row['bounced_count'] = (int) ($row['bounced_count'] ?? 0);
            $row['processed_count'] = (int) ($row['processed_count'] ?? 0);
            $row['opened_count'] = (int) ($row['opened_count'] ?? 0);
            $row['open_events_count'] = (int) ($row['open_events_count'] ?? 0);
            $row['unsubscribed_count'] = (int) ($row['unsubscribed_count'] ?? 0);
            $row['progress_percent'] = $this->percent($row['processed_count'], max($row['assigned_count'], $row['total_items']));
            $row['open_rate'] = $this->percent($row['opened_count'], $row['sent_count']);
        }
        unset($row);

        return $rows;
    }

    public function campaignMetrics(int $campaignId): array
    {
        $assigned = $this->safeScalar(
            'SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = :campaign_id AND status = "active"',
            ['campaign_id' => $campaignId]
        );

        $items = $this->safeFetchOne(
            'SELECT COUNT(ssi.id) AS total_items,
                    SUM(CASE WHEN ssi.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN ssi.status = "sent" THEN 1 ELSE 0 END) AS sent_count,
                    SUM(CASE WHEN ssi.status = "failed" THEN 1 ELSE 0 END) AS bounced_count,
                    SUM(CASE WHEN ssi.status = "skipped" THEN 1 ELSE 0 END) AS skipped_count,
                    SUM(CASE WHEN ssi.status IN ("sent", "failed", "skipped") THEN 1 ELSE 0 END) AS processed_count
             FROM send_session_items ssi
             INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
             WHERE ss.campaign_id = :campaign_id',
            ['campaign_id' => $campaignId]
        ) ?? [];

        $sent = (int) ($items['sent_count'] ?? 0);
        $processed = (int) ($items['processed_count'] ?? 0);
        $totalItems = (int) ($items['total_items'] ?? 0);
        $opens = $this->countUniqueOpens($campaignId);
        $openEvents = $this->countTotalOpenEvents($campaignId);
        $bounced = (int) ($items['bounced_count'] ?? 0);
        $unsubscribes = $this->safeScalar(
            'SELECT COUNT(DISTINCT r.id)
             FROM campaign_recipients cr
             INNER JOIN recipients r ON r.id = cr.recipient_id
             WHERE cr.campaign_id = :campaign_id
               AND cr.status = "active"
               AND r.unsubscribed_at IS NOT NULL',
            ['campaign_id' => $campaignId]
        );

        return [
            'assigned' => $assigned,
            'total_items' => $totalItems,
            'pending' => (int) ($items['pending_count'] ?? 0),
            'sent' => $sent,
            'opened' => $opens,
            'open_events' => $openEvents,
            'bounced' => $bounced,
            'skipped' => (int) ($items['skipped_count'] ?? 0),
            'processed' => $processed,
            'unsubscribed' => $unsubscribes,
            'progress_percent' => $this->percent($processed, max($assigned, $totalItems)),
            'open_rate' => $this->percent($opens, $sent),
            'bounce_rate' => $this->percent($bounced, max($sent + $bounced, 0)),
            'unsubscribe_rate' => $this->percent($unsubscribes, max($sent, $assigned)),
        ];
    }

    public function campaignTimeline(int $campaignId, int $days = 14): array
    {
        $days = max(7, min(60, $days));
        $rows = $this->safeFetchAll(
            'SELECT DATE(ssi.processed_at) AS day,
                    SUM(CASE WHEN ssi.status = "sent" THEN 1 ELSE 0 END) AS sent_count,
                    SUM(CASE WHEN ssi.status = "failed" THEN 1 ELSE 0 END) AS bounced_count
             FROM send_session_items ssi
             INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
             WHERE ss.campaign_id = :campaign_id
               AND ssi.processed_at IS NOT NULL
               AND ssi.processed_at >= DATE_SUB(CURDATE(), INTERVAL ' . $days . ' DAY)
             GROUP BY DATE(ssi.processed_at)
             ORDER BY day ASC',
            ['campaign_id' => $campaignId]
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'day' => (string) $row['day'],
                'sent' => (int) ($row['sent_count'] ?? 0),
                'bounced' => (int) ($row['bounced_count'] ?? 0),
            ];
        }

        return $result;
    }

    public function countUniqueOpens(?int $campaignId = null): int
    {
        if (!$this->hasColumn('send_session_items', 'opened_at')) {
            return 0;
        }

        $sql = 'SELECT COUNT(*)
                FROM (
                    SELECT ss.campaign_id, ssi.recipient_id
                    FROM send_session_items ssi
                    INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
                    WHERE ssi.opened_at IS NOT NULL';
        $params = [];

        if ($campaignId !== null) {
            $sql .= ' AND ss.campaign_id = :campaign_id';
            $params['campaign_id'] = $campaignId;
        }

        $sql .= ' GROUP BY ss.campaign_id, ssi.recipient_id
                ) opened_unique';

        return $this->safeScalar($sql, $params);
    }

    public function countTotalOpenEvents(?int $campaignId = null): int
    {
        if (!$this->hasColumn('send_session_items', 'open_count')) {
            return 0;
        }

        $sql = 'SELECT COALESCE(SUM(ssi.open_count), 0)
                FROM send_session_items ssi
                INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
                WHERE ssi.opened_at IS NOT NULL';
        $params = [];

        if ($campaignId !== null) {
            $sql .= ' AND ss.campaign_id = :campaign_id';
            $params['campaign_id'] = $campaignId;
        }

        return $this->safeScalar($sql, $params);
    }


    public function campaignOpenSummary(int $campaignId): array
    {
        $rows = $this->campaignRecipientOpenStatus($campaignId, 'all', '');
        $summary = [
            'total' => count($rows),
            'sent' => 0,
            'opened' => 0,
            'not_opened' => 0,
            'failed' => 0,
            'not_sent' => 0,
        ];

        foreach ($rows as $row) {
            if (($row['send_status'] ?? '') === 'sent') {
                $summary['sent']++;
            }
            if (($row['send_status'] ?? '') === 'failed') {
                $summary['failed']++;
            }
            if (($row['send_status'] ?? '') === 'not_sent') {
                $summary['not_sent']++;
            }
            if (!empty($row['opened_at'])) {
                $summary['opened']++;
            }
        }

        $summary['not_opened'] = max(0, $summary['sent'] - $summary['opened']);
        $summary['open_rate'] = $this->percent($summary['opened'], $summary['sent']);

        return $summary;
    }

    public function campaignRecipientOpenStatus(int $campaignId, string $filter = 'all', string $search = ''): array
    {
        $filter = in_array($filter, ['all', 'opened', 'not_opened', 'failed', 'not_sent'], true) ? $filter : 'all';
        $search = trim($search);

        $hasOpenedAt = $this->hasColumn('send_session_items', 'opened_at');
        $hasOpenCount = $this->hasColumn('send_session_items', 'open_count');

        $openedAtSql = $hasOpenedAt ? 'MAX(ssi.opened_at)' : 'NULL';
        $openCountSql = $hasOpenCount ? 'COALESCE(SUM(ssi.open_count), 0)' : '0';

        $sql = 'SELECT
                    r.id AS recipient_id,
                    r.email,
                    r.first_name,
                    r.last_name,
                    r.institution,
                    r.segment,
                    r.country,
                    COALESCE(sent_data.sent_count, 0) AS sent_count,
                    COALESCE(sent_data.failed_count, 0) AS failed_count,
                    COALESCE(sent_data.pending_count, 0) AS pending_count,
                    COALESCE(sent_data.skipped_count, 0) AS skipped_count,
                    sent_data.last_processed_at,
                    sent_data.opened_at,
                    COALESCE(sent_data.open_count, 0) AS open_count,
                    sent_data.last_error
                FROM campaign_recipients cr
                INNER JOIN recipients r ON r.id = cr.recipient_id
                LEFT JOIN (
                    SELECT
                        ssi.recipient_id,
                        SUM(CASE WHEN ssi.status = "sent" THEN 1 ELSE 0 END) AS sent_count,
                        SUM(CASE WHEN ssi.status = "failed" THEN 1 ELSE 0 END) AS failed_count,
                        SUM(CASE WHEN ssi.status = "pending" THEN 1 ELSE 0 END) AS pending_count,
                        SUM(CASE WHEN ssi.status = "skipped" THEN 1 ELSE 0 END) AS skipped_count,
                        MAX(ssi.processed_at) AS last_processed_at,
                        ' . $openedAtSql . ' AS opened_at,
                        ' . $openCountSql . ' AS open_count,
                        MAX(NULLIF(ssi.error_message, "")) AS last_error
                    FROM send_session_items ssi
                    INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
                    WHERE ss.campaign_id = :campaign_id_items
                    GROUP BY ssi.recipient_id
                ) sent_data ON sent_data.recipient_id = r.id
                WHERE cr.campaign_id = :campaign_id
                  AND cr.status = "active"';

        $params = [
            'campaign_id_items' => $campaignId,
            'campaign_id' => $campaignId,
        ];

        if ($search !== '') {
            $sql .= ' AND (r.email LIKE :search OR r.first_name LIKE :search OR r.last_name LIKE :search OR r.institution LIKE :search OR r.segment LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY r.email ASC';

        $rows = $this->safeFetchAll($sql, $params);
        $result = [];

        foreach ($rows as $row) {
            $sentCount = (int) ($row['sent_count'] ?? 0);
            $failedCount = (int) ($row['failed_count'] ?? 0);
            $pendingCount = (int) ($row['pending_count'] ?? 0);
            $skippedCount = (int) ($row['skipped_count'] ?? 0);
            $openedAt = (string) ($row['opened_at'] ?? '');
            $hasOpened = $openedAt !== '';

            if ($sentCount > 0) {
                $sendStatus = 'sent';
                $sendLabel = 'Enviado';
            } elseif ($failedCount > 0) {
                $sendStatus = 'failed';
                $sendLabel = 'Fallido';
            } elseif ($pendingCount > 0) {
                $sendStatus = 'pending';
                $sendLabel = 'Pendiente';
            } elseif ($skippedCount > 0) {
                $sendStatus = 'skipped';
                $sendLabel = 'Omitido';
            } else {
                $sendStatus = 'not_sent';
                $sendLabel = 'No enviado';
            }

            if ($filter === 'opened' && !$hasOpened) {
                continue;
            }
            if ($filter === 'not_opened' && !($sendStatus === 'sent' && !$hasOpened)) {
                continue;
            }
            if ($filter === 'failed' && $sendStatus !== 'failed') {
                continue;
            }
            if ($filter === 'not_sent' && $sendStatus !== 'not_sent') {
                continue;
            }

            $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));

            $result[] = [
                'recipient_id' => (int) ($row['recipient_id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'name' => $name,
                'institution' => (string) ($row['institution'] ?? ''),
                'segment' => (string) ($row['segment'] ?? ''),
                'country' => (string) ($row['country'] ?? ''),
                'send_status' => $sendStatus,
                'send_label' => $sendLabel,
                'opened' => $hasOpened,
                'opened_at' => $hasOpened ? $openedAt : null,
                'open_count' => (int) ($row['open_count'] ?? 0),
                'last_processed_at' => $row['last_processed_at'] ?? null,
                'last_error' => (string) ($row['last_error'] ?? ''),
            ];
        }

        return $result;
    }

    private function safeScalar(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $this->logDashboardError($e->getMessage(), $sql);
            return 0;
        }
    }

    private function safeFetchAll(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            $this->logDashboardError($e->getMessage(), $sql);
            return [];
        }
    }

    private function safeFetchOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            $this->logDashboardError($e->getMessage(), $sql);
            return null;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $table = str_replace('`', '', $table);
        $column = str_replace('`', '', $column);

        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = :table_name
                   AND COLUMN_NAME = :column_name'
            );
            $stmt->execute([
                'table_name' => $table,
                'column_name' => $column,
            ]);
            if ((int) $stmt->fetchColumn() > 0) {
                return true;
            }
        } catch (Throwable $e) {
            $this->logDashboardError($e->getMessage(), 'information_schema.COLUMNS');
        }

        try {
            $stmt = $this->db->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $this->db->quote($column));
            return (bool) $stmt->fetch();
        } catch (Throwable $e) {
            $this->logDashboardError($e->getMessage(), 'SHOW COLUMNS');
            return false;
        }
    }

    private function logDashboardError(string $message, string $context = ''): void
    {
        try {
            if ($this->hasTable('tracking_debug_logs')) {
                $stmt = $this->db->prepare('INSERT INTO tracking_debug_logs (event, data, created_at) VALUES (:event, :data, NOW())');
                $stmt->execute([
                    'event' => 'dashboard_exception',
                    'data' => json_encode([
                        'message' => mb_substr($message, 0, 500),
                        'context' => mb_substr($context, 0, 1500),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        } catch (Throwable) {
            // No detener el panel por un error de diagnostico.
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name'
            );
            $stmt->execute(['table_name' => $table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private function percent(int $part, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($part / $total) * 100);
    }
}
