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
            'activeRecipients' => (new Recipient())->countActive(),
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
        $subject = trim((string) $this->post('subject'));
        $htmlBody = (string) $this->post('html_body');
        $textBody = (string) $this->post('text_body');
        $fromName = trim((string) $this->post('from_name'));
        $fromEmail = trim((string) $this->post('from_email'));
        if ($subject === '' || $htmlBody === '' || $textBody === '' || $fromEmail === '') {
            Session::flash('error', 'Asunto, remitente y cuerpos HTML / texto son obligatorios.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }
        (new CampaignMessage())->saveForCampaign($campaignId, [
            'subject' => $subject,
            'from_name' => $fromName,
            'from_email' => $fromEmail,
            'reply_to' => trim((string) $this->post('reply_to')),
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);
        AuditLogger::log('campaign.message.saved', 'campaign', $campaignId);
        Session::flash('success', 'Mensaje guardado correctamente.');
        $this->redirect('/campaigns/' . $campaignId . '/message');
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
            'recipientsCount' => (new Recipient())->countActive(),
            'settings' => $settings,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function startSend(string $id): void
    {
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        try {
            $sessionId = (new SendService())->start($campaignId);
            $this->redirect('/sending/' . $sessionId);
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/campaigns/' . $campaignId . '/send');
        }
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
