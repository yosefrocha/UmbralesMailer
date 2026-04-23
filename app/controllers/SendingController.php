<?php

declare(strict_types=1);

final class SendingController extends Controller
{
    public function show(string $id): void
    {
        $sessionId = $this->intId($id);
        $sessionModel = new SendSession();
        $session = $sessionModel->find($sessionId);
        if (!$session) {
            $this->redirect('/campaigns');
        }
        $this->view('sending/show', [
            'title' => 'Monitoreo de envío',
            'session' => $sessionModel->refreshCounts($sessionId),
            'items' => $sessionModel->recentItems($sessionId),
            'batchSize' => (require CONFIG_PATH . '/app.php')['send_batch_size'] ?? 10,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function status(string $id): void
    {
        $sessionId = $this->intId($id);
        $sessionModel = new SendSession();
        $session = $sessionModel->refreshCounts($sessionId);
        $items = $sessionModel->recentItems($sessionId, 10);
        $this->json(['session' => $session, 'items' => $items]);
    }

    public function process(string $id): void
    {
        $this->requireCsrf();
        $sessionId = $this->intId($id);
        $limit = max(1, (int) $this->post('limit', 10));
        try {
            $session = (new SendService())->process($sessionId, $limit);
            $items = (new SendSession())->recentItems($sessionId, 10);
            $this->json(['ok' => true, 'session' => $session, 'items' => $items]);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function pause(string $id): void
    {
        $this->requireCsrf();
        $sessionId = $this->intId($id);
        (new SendSession())->setStatus($sessionId, 'paused');
        AuditLogger::log('send.paused', 'send_session', $sessionId);
        $this->json(['ok' => true]);
    }

    public function resume(string $id): void
    {
        $this->requireCsrf();
        $sessionId = $this->intId($id);
        (new SendSession())->setStatus($sessionId, 'processing');
        AuditLogger::log('send.resumed', 'send_session', $sessionId);
        $this->json(['ok' => true]);
    }
}
