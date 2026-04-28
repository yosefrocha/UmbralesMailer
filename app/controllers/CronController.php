<?php

declare(strict_types=1);

final class CronController extends Controller
{
    public function scheduledSend(): void
    {
        $settings = (new SettingsService())->all();
        $expected = trim((string) ($settings['scheduled_cron_key'] ?? ''));
        $provided = trim((string) ($_GET['key'] ?? ''));

        header('Content-Type: application/json; charset=UTF-8');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            http_response_code(403);
            echo json_encode([
                'ok' => false,
                'error' => 'Cron key inválida o no configurada.',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $limit = max(1, min(200, (int) ($_GET['limit'] ?? 50)));

        try {
            $result = (new CampaignDeliverySendService())->processDue($limit, null);
            echo json_encode([
                'ok' => true,
                'result' => $result,
                'executed_at' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'ok' => false,
                'error' => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
