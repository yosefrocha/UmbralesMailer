<?php

declare(strict_types=1);

final class AuditLogger
{
    public static function log(string $action, string $entityType, ?int $entityId = null, array $details = []): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare('INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, details_json) VALUES (:actor_user_id, :action, :entity_type, :entity_id, :details_json)');
            $stmt->execute([
                'actor_user_id' => Auth::user()['id'] ?? null,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
        } catch (Throwable $e) {
            self::logFile('audit-error', $e->getMessage());
        }
    }

    public static function logFile(string $tag, string $message): void
    {
        $dir = STORAGE_PATH . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/app.log', '[' . date('Y-m-d H:i:s') . '] ' . $tag . ': ' . $message . PHP_EOL, FILE_APPEND);
    }
}
