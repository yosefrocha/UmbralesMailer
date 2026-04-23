<?php

declare(strict_types=1);

final class RecipientsController extends Controller
{
    public function index(): void
    {
        $search = trim((string) $this->get('q'));
        $status = trim((string) $this->get('status'));
        $this->view('recipients/index', [
            'title' => 'Destinatarios',
            'recipients' => (new Recipient())->all($search, $status),
            'search' => $search,
            'statusFilter' => $status,
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'importResult' => Session::getFlash('import_result'),
        ]);
    }

    public function create(): void
    {
        $this->view('recipients/form', [
            'title' => 'Crear destinatario',
            'recipient' => null,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->validate();
        $recipientModel = new Recipient();
        $recipientModel->upsert($data);
        AuditLogger::log('recipient.created_or_updated', 'recipient', null, $data);
        Session::flash('success', 'Destinatario guardado correctamente.');
        $this->redirect('/recipients');
    }

    public function edit(string $id): void
    {
        $recipient = (new Recipient())->find($this->intId($id));
        if (!$recipient) {
            $this->redirect('/recipients');
        }
        $this->view('recipients/form', [
            'title' => 'Editar destinatario',
            'recipient' => $recipient,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $recipientId = $this->intId($id);
        $data = $this->validate();
        (new Recipient())->updateRecipient($recipientId, $data);
        AuditLogger::log('recipient.updated', 'recipient', $recipientId, $data);
        Session::flash('success', 'Destinatario actualizado correctamente.');
        $this->redirect('/recipients');
    }

    public function import(): void
    {
        $this->requireCsrf();
        if (!isset($_FILES['csv']) || !is_uploaded_file($_FILES['csv']['tmp_name'])) {
            Session::flash('error', 'Debes seleccionar un archivo CSV válido.');
            $this->redirect('/recipients');
        }

        try {
            $service = new CsvImportService();
            $result = $service->import($_FILES['csv']['tmp_name'], (string) $_FILES['csv']['name'], (int) (Auth::user()['id'] ?? 0));
            Session::flash('success', 'Importación ejecutada correctamente.');
            Session::flash('import_result', $result);
        } catch (Throwable $e) {
            Session::flash('error', 'Error al importar CSV: ' . $e->getMessage());
        }

        $this->redirect('/recipients');
    }

    public function downloadTemplate(): void
    {
        $file = BASE_PATH . '/assets/templates/recipients_template.csv';

        if (!is_file($file)) {
            http_response_code(404);
            echo 'No se encontró la plantilla CSV.';
            exit;
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="recipients_template.csv"');
        header('Content-Length: ' . (string) filesize($file));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($file);
        exit;
    }

    private function validate(): array
    {
        $email = trim((string) $this->post('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Debes capturar un correo válido.');
            $this->redirect($_SERVER['REQUEST_URI']);
        }
        return [
            'email' => $email,
            'first_name' => trim((string) $this->post('first_name')),
            'last_name' => trim((string) $this->post('last_name')),
            'institution' => trim((string) $this->post('institution')),
            'country' => trim((string) $this->post('country')),
            'segment' => trim((string) $this->post('segment')),
            'status' => trim((string) $this->post('status')) ?: 'active',
            'consent_at' => trim((string) $this->post('consent_at')),
        ];
    }
}
