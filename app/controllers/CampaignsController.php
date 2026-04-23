<?php

declare(strict_types=1);

final class CampaignsController extends Controller
{
    public function index(): void
    {
        $this->view('campaigns/index', [
            'title' => 'Campañas',
            'campaigns' => (new Campaign())->all(),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function create(): void
    {
        $this->view('campaigns/form', [
            'title' => 'Crear campaña',
            'campaign' => null,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();

        $data = $this->validate();
        $data['created_by'] = (int) (Auth::user()['id'] ?? 0);

        $id = (new Campaign())->create($data);

        AuditLogger::log('campaign.created', 'campaign', $id, $data);
        Session::flash('success', 'Campaña creada correctamente.');

        $this->redirect('/campaigns/' . $id);
    }

    public function show(string $id): void
    {
        $campaignId = $this->intId($id);
        $stats = (new Campaign())->stats($campaignId);

        if (!$stats['campaign']) {
            $this->redirect('/campaigns');
        }

        $this->view('campaigns/show', [
            'title' => 'Detalle de campaña',
            'campaign' => $stats['campaign'],
            'message' => $stats['message'],
            'latestSession' => $stats['latest_session'],
            'activeRecipients' => (new CampaignRecipient())->countActiveByCampaign($campaignId),
        ]);
    }

    public function edit(string $id): void
    {
        $campaign = (new Campaign())->find($this->intId($id));

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $this->view('campaigns/form', [
            'title' => 'Editar campaña',
            'campaign' => $campaign,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $data = $this->validate();

        (new Campaign())->updateCampaign($campaignId, $data);

        AuditLogger::log('campaign.updated', 'campaign', $campaignId, $data);
        Session::flash('success', 'Campaña actualizada correctamente.');

        $this->redirect('/campaigns/' . $campaignId);
    }

    public function activate(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        if (($campaign['status'] ?? '') === 'cancelled') {
            Session::flash('error', 'Una campaña cancelada ya no puede activarse.');
            $this->redirect('/campaigns');
        }

        (new Campaign())->activateCampaign($campaignId);

        AuditLogger::log('campaign.activated', 'campaign', $campaignId);
        Session::flash('success', 'Campaña activada correctamente.');

        $this->redirect('/campaigns/' . $campaignId);
    }

    public function deactivate(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        if (($campaign['status'] ?? '') === 'cancelled') {
            Session::flash('error', 'Una campaña cancelada ya no puede desactivarse.');
            $this->redirect('/campaigns');
        }

        (new Campaign())->deactivateCampaign($campaignId);

        AuditLogger::log('campaign.deactivated', 'campaign', $campaignId);
        Session::flash('success', 'Campaña desactivada correctamente.');

        $this->redirect('/campaigns/' . $campaignId);
    }

    public function delete(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);

        (new Campaign())->deleteCampaign($campaignId);

        AuditLogger::log('campaign.cancelled', 'campaign', $campaignId);
        Session::flash('success', 'Campaña marcada como cancelada.');

        $this->redirect('/campaigns');
    }

    public function messageForm(string $id): void
    {
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $message = (new CampaignMessage())->findByCampaign($campaignId);
        $settings = (new SettingsService())->all();

        $this->view('campaigns/message', [
            'title' => 'Mensaje de campaña',
            'campaign' => $campaign,
            'message' => $message,
            'settings' => $settings,
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function saveMessage(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $contentMode = trim((string) $this->post('content_mode'));
        $subject = trim((string) $this->post('subject'));
        $textBody = (string) $this->post('text_body');
        $htmlBody = (string) $this->post('html_body');
        $fromName = trim((string) $this->post('from_name'));
        $fromEmail = trim((string) $this->post('from_email'));
        $replyTo = trim((string) $this->post('reply_to'));

        if (!in_array($contentMode, ['text', 'html'], true)) {
            Session::flash('error', 'Debes seleccionar un tipo de contenido válido.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        if ($subject === '' || $fromEmail === '') {
            Session::flash('error', 'Asunto y correo remitente son obligatorios.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        if ($contentMode === 'text' && trim($textBody) === '') {
            Session::flash('error', 'Debes capturar el contenido en texto plano.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        if ($contentMode === 'html' && trim($htmlBody) === '') {
            Session::flash('error', 'Debes capturar el contenido HTML.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        (new Campaign())->updateContentMode($campaignId, $contentMode);

        (new CampaignMessage())->saveForCampaign($campaignId, [
            'subject' => $subject,
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'reply_to' => $replyTo,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);

        AuditLogger::log('campaign.message.saved', 'campaign', $campaignId, [
            'content_mode' => $contentMode,
        ]);

        Session::flash('success', 'Mensaje guardado correctamente.');
        $this->redirect('/campaigns/' . $campaignId . '/message');
    }

    public function recipients(string $id): void
    {
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $campaignRecipientModel = new CampaignRecipient();

        $this->view('campaigns/recipients', [
            'title' => 'Destinatarios de campaña',
            'campaign' => $campaign,
            'recipients' => $campaignRecipientModel->allByCampaign($campaignId),
            'assignedCount' => $campaignRecipientModel->countActiveByCampaign($campaignId),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'importResult' => Session::getFlash('import_result'),
        ]);
    }

    public function importRecipients(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            Session::flash('error', 'Debes seleccionar un archivo CSV válido.');
            $this->redirect('/campaigns/' . $campaignId . '/recipients');
        }

        try {
            $service = new CampaignRecipientImportService();
            $result = $service->import(
                $campaignId,
                $_FILES['csv']['tmp_name'],
                (string) $_FILES['csv']['name'],
                (int) (Auth::user()['id'] ?? 0)
            );

            Session::flash('success', 'Destinatarios importados correctamente a la campaña.');
            Session::flash('import_result', $result);
        } catch (Throwable $e) {
            Session::flash('error', 'Error al importar destinatarios: ' . $e->getMessage());
        }

        $this->redirect('/campaigns/' . $campaignId . '/recipients');
    }

    public function removeRecipient(string $id, string $recipientId): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $recipientIdInt = $this->intId($recipientId);

        (new CampaignRecipient())->remove($campaignId, $recipientIdInt);

        Session::flash('success', 'Destinatario eliminado de la campaña.');
        $this->redirect('/campaigns/' . $campaignId . '/recipients');
    }

    public function sendSetup(string $id): void
    {
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);
        $message = (new CampaignMessage())->findByCampaign($campaignId);
        $settings = (new SettingsService())->all();

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $this->view('campaigns/send', [
            'title' => 'Preparar envío',
            'campaign' => $campaign,
            'message' => $message,
            'recipientsCount' => (new CampaignRecipient())->countActiveByCampaign($campaignId),
            'settings' => $settings,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function startSend(string $id): void
    {
        $this->requireCsrf();

        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        if (($campaign['status'] ?? '') !== 'active') {
            Session::flash('error', 'Solo las campañas activas pueden enviarse.');
            $this->redirect('/campaigns/' . $campaignId . '/send');
        }

        try {
            $sessionId = (new SendService())->start($campaignId);
            $this->redirect('/sending/' . $sessionId);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/campaigns/' . $campaignId . '/send');
        }
    }

    public function downloadRecipientsTemplate(string $id): void
    {
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);

        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $filename = 'plantilla_destinatarios_campana.csv';
        $csv = implode("\n", [
            'correo,nombre,apellido,inst,pais,segmento,estado,consent',
            'maria@ejemplo.com,Maria,Garcia,Instituto Umbrales,Mexico,Docentes,activo,2026-04-20 10:00:00',
            'juan@ejemplo.com,Juan,Perez,Colegio Horizonte,Colombia,Directivos,activo,2026-04-20 11:00:00',
        ]);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    public function redirectSequenceToMessage(string $id): void
    {
        $campaignId = $this->intId($id);
        $this->redirect('/campaigns/' . $campaignId . '/message');
    }

    private function validate(): array
    {
        $name = trim((string) $this->post('name'));

        if ($name === '') {
            Session::flash('error', 'El nombre de la campaña es obligatorio.');
            $this->redirect($_SERVER['REQUEST_URI']);
        }

        return [
            'name' => $name,
            'description' => trim((string) $this->post('description')),
        ];
    }
}