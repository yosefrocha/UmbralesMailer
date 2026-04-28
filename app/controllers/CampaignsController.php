<?php

declare(strict_types=1);

final class CampaignsController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $this->view('campaigns/index', [
            'title'     => 'Campañas',
            'campaigns' => (new Campaign())->all(),
            'error'     => Session::getFlash('error'),
            'success'   => Session::getFlash('success'),
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $this->view('campaigns/form', [
            'title'    => 'Crear campaña',
            'campaign' => null,
            'error'    => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
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
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $stats = (new Campaign())->stats($campaignId);
        if (!$stats['campaign']) {
            $this->redirect('/campaigns');
        }
        $analytics = new CampaignAnalytics();

        $this->view('campaigns/show', [
            'title'            => 'Detalle de campaña',
            'campaign'         => $stats['campaign'],
            'message'          => $stats['message'],
            'latestSession'    => $stats['latest_session'],
            'allSessions'      => (new SendSession())->allByCampaign($campaignId),
            'activeRecipients' => (new CampaignRecipient())->countActiveByCampaign($campaignId),
            'campaignMetrics'  => $analytics->campaignMetrics($campaignId),
            'campaignTimeline' => $analytics->campaignTimeline($campaignId, 14),
        ]);
    }

    public function opens(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $filter = Sanitizer::clean((string) ($_GET['filter'] ?? 'all'));
        $search = Sanitizer::clean((string) ($_GET['search'] ?? ''));

        if (!in_array($filter, ['all', 'opened', 'not_opened', 'failed', 'not_sent'], true)) {
            $filter = 'all';
        }

        $analytics = new CampaignAnalytics();
        $rows = $analytics->campaignRecipientOpenStatus($campaignId, $filter, $search);
        $summary = $analytics->campaignOpenSummary($campaignId);

        $this->view('campaigns/opens', [
            'title'    => 'Aperturas de campaña',
            'campaign' => $campaign,
            'rows'     => $rows,
            'summary'  => $summary,
            'filter'   => $filter,
            'search'   => $search,
        ]);
    }

    public function edit(string $id): void
    {
        Auth::requireAuth();
        $campaign = (new Campaign())->find($this->intId($id));
        if (!$campaign) {
            $this->redirect('/campaigns');
        }
        $this->view('campaigns/form', [
            'title'    => 'Editar campaña',
            'campaign' => $campaign,
            'error'    => Session::getFlash('error'),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireAuth();
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
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign || ($campaign['status'] ?? '') === 'cancelled') {
            Session::flash('error', 'No se puede activar esta campaña.');
            $this->redirect('/campaigns');
        }
        (new Campaign())->activateCampaign($campaignId);
        AuditLogger::log('campaign.activated', 'campaign', $campaignId);
        Session::flash('success', 'Campaña activada correctamente.');
        $this->redirect('/campaigns/' . $campaignId);
    }

    public function deactivate(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        (new Campaign())->deactivateCampaign($campaignId);
        AuditLogger::log('campaign.deactivated', 'campaign', $campaignId);
        Session::flash('success', 'Campaña desactivada correctamente.');
        $this->redirect('/campaigns/' . $campaignId);
    }

    public function delete(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        (new Campaign())->deleteCampaign($campaignId);
        AuditLogger::log('campaign.cancelled', 'campaign', $campaignId);
        Session::flash('success', 'Campaña marcada como cancelada.');
        $this->redirect('/campaigns');
    }

    public function messageForm(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }
        $message  = (new CampaignMessage())->findByCampaign($campaignId);
        $settings = (new SettingsService())->all();
        $this->view('campaigns/message', [
            'title'    => 'Mensaje de campaña',
            'campaign' => $campaign,
            'message'  => $message,
            'settings' => $settings,
            'error'    => Session::getFlash('error'),
            'success'  => Session::getFlash('success'),
        ]);
    }

    public function saveMessage(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $campaign = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $contentMode = trim((string) $this->post('content_mode'));
        $subject     = Sanitizer::clean((string) $this->post('subject'));
        $textBody    = (string) $this->post('text_body');
        $htmlBody    = (string) $this->post('html_body');
        $fromName    = Sanitizer::clean((string) $this->post('from_name'));
        $fromEmail   = Sanitizer::email((string) $this->post('from_email'));
        $replyTo     = Sanitizer::email((string) $this->post('reply_to'));

        if (!in_array($contentMode, ['text', 'html'], true)) {
            Session::flash('error', 'Debes seleccionar un tipo de contenido válido.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        if ($subject === '' || $fromEmail === '') {
            Session::flash('error', 'Asunto y correo remitente son obligatorios.');
            $this->redirect('/campaigns/' . $campaignId . '/message');
        }

        if (!Sanitizer::isValidEmail($fromEmail)) {
            Session::flash('error', 'El correo remitente no es válido.');
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
            'subject'    => $subject,
            'from_name'  => $fromName,
            'from_email' => $fromEmail,
            'reply_to'   => $replyTo,
            'html_body'  => $contentMode === 'html' ? $htmlBody : null,
            'text_body'  => $contentMode === 'text' ? $textBody : null,
        ]);

        AuditLogger::log('campaign.message.saved', 'campaign', $campaignId, ['content_mode' => $contentMode]);
        Session::flash('success', 'Mensaje guardado correctamente.');
        $this->redirect('/campaigns/' . $campaignId . '/message');
    }

    public function previewMessage(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        $message    = (new CampaignMessage())->findByCampaign($campaignId);
        if (!$campaign || !$message) {
            $this->redirect('/campaigns');
        }
        $this->view('campaigns/preview', [
            'title'    => 'Vista previa',
            'campaign' => $campaign,
            'message'  => $message,
        ]);
    }

    public function sendTest(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $toEmail    = Sanitizer::email((string) $this->post('test_email'));

        if (!Sanitizer::isValidEmail($toEmail)) {
            $this->json(['ok' => false, 'error' => 'Correo de prueba inválido.']);
        }

        try {
            $result = (new SendService())->sendTest($campaignId, $toEmail);
            if ($result['ok'] ?? false) {
                $this->json(['ok' => true, 'message' => "Correo de prueba enviado a {$toEmail}"]);
            } else {
                $this->json(['ok' => false, 'error' => $result['error'] ?? 'Error al enviar.']);
            }
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function recipients(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }
        $campaignRecipientModel = new CampaignRecipient();
        $recipientModel         = new Recipient();

        $search  = Sanitizer::clean((string) ($_GET['search'] ?? ''));
        $segment = Sanitizer::clean((string) ($_GET['segment'] ?? ''));
        $country = Sanitizer::clean((string) ($_GET['country'] ?? ''));
        $institution = Sanitizer::clean((string) ($_GET['institution'] ?? ''));

        $this->view('campaigns/recipients', [
            'title'               => 'Destinatarios de campaña',
            'campaign'            => $campaign,
            'recipients'          => $campaignRecipientModel->allByCampaign($campaignId),
            'assignedCount'       => $campaignRecipientModel->countActiveByCampaign($campaignId),
            'availableRecipients' => $recipientModel->availableForCampaign($campaignId, $search, $segment, $country, $institution),
            'segments'            => $recipientModel->getSegments(),
            'countries'           => $recipientModel->getCountries(),
            'institutions'        => $recipientModel->getInstitutions(),
            'search'              => $search,
            'segment'             => $segment,
            'country'             => $country,
            'institution'         => $institution,
            'error'               => Session::getFlash('error'),
            'success'             => Session::getFlash('success'),
            'importResult'        => Session::getFlash('import_result'),
            'validationResult'    => Session::getFlash('validation_result'),
        ]);
    }

    public function assignRecipient(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId  = $this->intId($id);
        $recipientId = (int) $this->post('recipient_id');
        $search      = Sanitizer::clean((string) $this->post('search'));
        $segment     = Sanitizer::clean((string) $this->post('segment'));
        $country     = Sanitizer::clean((string) $this->post('country'));
        $institution = Sanitizer::clean((string) $this->post('institution'));

        $query = '?tab=assign'
            . ($search  !== '' ? '&search='  . urlencode($search)  : '')
            . ($segment !== '' ? '&segment=' . urlencode($segment) : '')
            . ($country !== '' ? '&country=' . urlencode($country) : '')
            . ($institution !== '' ? '&institution=' . urlencode($institution) : '');

        if ($recipientId <= 0) {
            Session::flash('error', 'Destinatario inválido.');
            $this->redirect('/campaigns/' . $campaignId . '/recipients' . $query);
        }

        (new CampaignRecipient())->attach($campaignId, $recipientId, 'manual');
        Session::flash('success', 'Destinatario asignado correctamente.');
        $this->redirect('/campaigns/' . $campaignId . '/recipients' . $query);
    }

    public function assignBulk(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $segment    = Sanitizer::clean((string) $this->post('segment'));
        $country    = Sanitizer::clean((string) $this->post('country'));
        $institution = Sanitizer::clean((string) $this->post('institution'));

        $recipientModel = new Recipient();
        $available      = $recipientModel->availableForCampaign($campaignId, '', $segment, $country, $institution);

        $campaignRecipientModel = new CampaignRecipient();
        $count = 0;
        foreach ($available as $recipient) {
            $campaignRecipientModel->attach($campaignId, (int) $recipient['id'], 'bulk_assign');
            $count++;
        }

        Session::flash('success', "{$count} destinatarios asignados correctamente.");
        $query = '?tab=assign'
            . ($segment !== '' ? '&segment=' . urlencode($segment) : '')
            . ($country !== '' ? '&country=' . urlencode($country) : '')
            . ($institution !== '' ? '&institution=' . urlencode($institution) : '');
        $this->redirect('/campaigns/' . $campaignId . '/recipients' . $query);
    }

    public function importRecipients(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            Session::flash('error', 'Debes seleccionar un archivo CSV válido.');
            $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=import');
        }

        try {
            $service = new CampaignRecipientImportService();
            $validation = $service->validate($_FILES['csv']['tmp_name']);
            if (!empty($validation['errors'])) {
                Session::flash('error', 'El CSV tiene errores. No se guardó ningún destinatario.');
                Session::flash('validation_result', $validation);
                $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=import');
            }

            $result  = $service->import(
                $campaignId,
                $_FILES['csv']['tmp_name'],
                (string) $_FILES['csv']['name'],
                (int) (Auth::user()['id'] ?? 0)
            );
            Session::flash('success', 'Destinatarios importados y asignados correctamente.');
            Session::flash('import_result', $result);
        } catch (Throwable $e) {
            Session::flash('error', 'Error al importar: ' . $e->getMessage());
            $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=import');
        }

        $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=assigned');
    }

    public function removeRecipient(string $id, string $recipientId): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId     = $this->intId($id);
        $recipientIdInt = $this->intId($recipientId);
        (new CampaignRecipient())->remove($campaignId, $recipientIdInt);
        Session::flash('success', 'Destinatario eliminado de la campaña.');
        $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=assigned');
    }

    public function sendSetup(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        $message    = (new CampaignMessage())->findByCampaign($campaignId);
        $settings   = (new SettingsService())->all();
        if (!$campaign) {
            $this->redirect('/campaigns');
        }
        $this->view('campaigns/send', [
            'title'           => 'Preparar envío',
            'campaign'        => $campaign,
            'message'         => $message,
            'recipientsCount' => (new CampaignRecipient())->countActiveByCampaign($campaignId),
            'settings'        => $settings,
            'error'           => Session::getFlash('error'),
            'success'         => Session::getFlash('success'),
        ]);
    }

    public function startSend(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
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
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }
        $filename = 'plantilla_destinatarios.csv';
        $csv = implode("\n", [
            'correo,nombre,apellido,inst,pais,segmento,estado,consent',
            'maria@ejemplo.com,María,García,Instituto Umbrales,Mexico,Docentes,activo,2026-04-20 10:00:00',
            'juan@ejemplo.com,Juan,Pérez,Colegio Horizonte,Colombia,Directivos,activo,2026-04-20 11:00:00',
        ]);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    public function exportResults(string $id): void
    {
        Auth::requireAuth();
        $campaignId = $this->intId($id);
        $campaign   = (new Campaign())->find($campaignId);
        if (!$campaign) {
            $this->redirect('/campaigns');
        }

        $db   = Database::connection();
        $stmt = $db->prepare(
            'SELECT r.email, r.first_name, r.last_name, r.institution, r.segment,
                    ssi.status AS send_status, ssi.processed_at, ssi.error_message
             FROM send_session_items ssi
             INNER JOIN recipients r ON r.id = ssi.recipient_id
             INNER JOIN send_sessions ss ON ss.id = ssi.send_session_id
             WHERE ss.campaign_id = :campaign_id
             ORDER BY ssi.id ASC'
        );
        $stmt->execute(['campaign_id' => $campaignId]);
        $rows = $stmt->fetchAll();

        $filename = 'resultados_campana_' . $campaignId . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        echo "correo,nombre,apellido,institución,segmento,estado_envío,fecha_procesado,error\n";
        foreach ($rows as $row) {
            echo implode(',', array_map(
                fn ($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"',
                [
                    $row['email'], $row['first_name'], $row['last_name'],
                    $row['institution'], $row['segment'],
                    $row['send_status'], $row['processed_at'], $row['error_message'],
                ]
            )) . "\n";
        }
        exit;
    }

    public function validateRecipientsCsv(string $id): void
    {
        Auth::requireAuth();
        $this->requireCsrf();
        $campaignId = $this->intId($id);

        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            Session::flash('error', 'Debes seleccionar un archivo CSV válido.');
            $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=import');
        }

        try {
            $validation = (new CampaignRecipientImportService())->validate($_FILES['csv']['tmp_name']);
            Session::flash('validation_result', $validation);
            if (!empty($validation['errors'])) {
                Session::flash('error', 'El CSV contiene errores. Corrígelos antes de importar.');
            } else {
                Session::flash('success', 'Validación correcta. El archivo está listo para importar.');
            }
        } catch (Throwable $e) {
            Session::flash('error', 'Error al validar: ' . $e->getMessage());
        }

        $this->redirect('/campaigns/' . $campaignId . '/recipients?tab=import');
    }

    private function validate(): array
    {
        $name = Sanitizer::clean((string) $this->post('name'));
        if ($name === '') {
            Session::flash('error', 'El nombre de la campaña es obligatorio.');
            $this->redirect($_SERVER['REQUEST_URI']);
        }
        if (Sanitizer::isSuspicious($name)) {
            Session::flash('error', 'El nombre contiene caracteres no permitidos.');
            $this->redirect($_SERVER['REQUEST_URI']);
        }
        return [
            'name'        => $name,
            'description' => Sanitizer::clean((string) $this->post('description')),
        ];
    }
}