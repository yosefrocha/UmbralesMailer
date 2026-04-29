<?php

declare(strict_types=1);

final class ScheduledCampaignsController extends Controller
{
    public function show(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $message = (new CampaignMessage())->findByCampaign($campaignId);
        $deliveryModel = new CampaignDelivery();
        $cronUrl = Auth::isAdmin() ? (new CampaignScheduleService())->cronUrl() : '';

        $this->view('campaigns/schedule', [
            'title' => 'Secuencia programada',
            'campaign' => $campaign,
            'message' => $message,
            'activeRecipients' => (new CampaignRecipient())->countActiveByCampaign($campaignId),
            'summary' => $deliveryModel->summaryByCampaign($campaignId),
            'deliveries' => $deliveryModel->listByCampaign($campaignId, 500),
            'cronUrl' => $cronUrl,
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'lastResult' => Session::getFlash('scheduled_result'),
        ]);
    }

    public function store(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $startAt = trim((string) $this->post('start_at'));
        $quotaPerRecipient = (int) $this->post('quota_per_recipient', 10);
        $intervalDays = (int) $this->post('interval_days', 2);

        if ($startAt === '') {
            Session::flash('error', 'Debes indicar fecha y hora de inicio.');
            $this->redirect('/campaigns/' . $campaignId . '/schedule');
        }

        $startAt = str_replace('T', ' ', $startAt);
        if (strlen($startAt) === 16) {
            $startAt .= ':00';
        }

        try {
            $result = (new CampaignScheduleService())->scheduleDrip($campaignId, $startAt, $quotaPerRecipient, $intervalDays);
            Session::flash('success', 'Secuencia programada: ' . (int) $result['recipients'] . ' destinatarios, ' . (int) $result['quota_per_recipient'] . ' mensajes por destinatario, cada ' . (int) $result['interval_days'] . ' días.');
            Session::flash('scheduled_result', $result);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/campaigns/' . $campaignId . '/schedule');
    }

    public function cancel(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        try {
            $cancelled = (new CampaignScheduleService())->cancelPending($campaignId);
            Session::flash('success', 'Programación cancelada. Pendientes cancelados: ' . $cancelled . '.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/campaigns/' . $campaignId . '/schedule');
    }

    public function markResponded(string $id, string $recipientId): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $rid = $this->intId($recipientId);
        $note = trim((string) $this->post('response_note', ''));
        try {
            (new CampaignRecipient())->markResponded($campaignId, $rid, $note);
            Session::flash('success', 'Respuesta registrada. Los envíos pendientes para ese destinatario fueron detenidos.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/campaigns/' . $campaignId . '/schedule');
    }

    public function clearResponded(string $id, string $recipientId): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $rid = $this->intId($recipientId);
        try {
            (new CampaignRecipient())->clearResponded($campaignId, $rid);
            Session::flash('success', 'Marca de respuesta eliminada. Si necesitas continuar la secuencia, vuelve a guardar la programación.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/campaigns/' . $campaignId . '/schedule');
    }

    public function processNow(string $id): void
    {
        Auth::requireAdmin();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $limit = max(1, min(200, (int) $this->post('limit', 50)));
        try {
            $result = (new CampaignDeliverySendService())->processDue($limit, $campaignId);
            Session::flash('success', 'Pendientes procesados. Enviados: ' . (int) $result['sent'] . ', fallidos: ' . (int) $result['failed'] . ', omitidos: ' . (int) $result['skipped'] . '.');
            Session::flash('scheduled_result', $result);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/campaigns/' . $campaignId . '/schedule');
    }
}
