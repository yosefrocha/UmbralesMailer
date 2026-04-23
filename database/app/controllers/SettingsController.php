<?php

declare(strict_types=1);

final class SettingsController extends Controller
{
    public function index(): void
    {
        $this->view('settings/index', [
            'title' => 'Configuración',
            'settings' => (new SettingsService())->all(),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function save(): void
    {
        $this->requireCsrf();
        $data = [
            'ses_region' => trim((string) $this->post('ses_region')),
            'ses_key' => trim((string) $this->post('ses_key')),
            'ses_secret' => trim((string) $this->post('ses_secret')),
            'ses_from_email' => trim((string) $this->post('ses_from_email')),
            'ses_from_name' => trim((string) $this->post('ses_from_name')),
            'ses_reply_to' => trim((string) $this->post('ses_reply_to')),
            'ses_configuration_set' => trim((string) $this->post('ses_configuration_set')),
        ];
        if ($data['ses_region'] === '' || $data['ses_from_email'] === '') {
            Session::flash('error', 'Región y remitente por defecto son obligatorios.');
            $this->redirect('/settings');
        }
        (new SettingsService())->save($data);
        AuditLogger::log('settings.updated', 'settings');
        Session::flash('success', 'Configuración guardada correctamente.');
        $this->redirect('/settings');
    }
}
