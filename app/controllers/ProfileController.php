<?php

declare(strict_types=1);

final class ProfileController extends Controller
{
    public function showPasswordForm(): void
    {
        $this->view('profile/password', [
            'title' => 'Cambiar contraseña',
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'force' => (bool) Session::get('force_password_change', false),
        ]);
    }

    public function updatePassword(): void
    {
        $this->requireCsrf();
        $password = (string) $this->post('password');
        $confirm = (string) $this->post('password_confirmation');
        if (strlen($password) < 8) {
            Session::flash('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/profile/password');
        }
        if ($password !== $confirm) {
            Session::flash('error', 'La confirmación de contraseña no coincide.');
            $this->redirect('/profile/password');
        }

        $userId = (int) (Auth::user()['id'] ?? 0);
        (new User())->updatePassword($userId, $password);
        Session::forget('force_password_change');
        Session::flash('success', 'Contraseña actualizada correctamente.');
        AuditLogger::log('user.password.changed', 'user', $userId);
        $this->redirect('/profile/password');
    }
}
