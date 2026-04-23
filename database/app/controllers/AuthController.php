<?php

declare(strict_types=1);

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $this->view('auth/login', [
            'title' => 'Iniciar sesión',
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function login(): void
    {
        $this->requireCsrf();

        $email = trim((string) $this->post('email'));
        $password = (string) $this->post('password');
        if ($email === '' || $password === '') {
            Session::flash('error', 'Correo y contraseña son obligatorios.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if (!$user || !(bool) $user['is_active']) {
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        $authenticated = false;
        if (password_verify($password, $user['password_hash'])) {
            $authenticated = true;
        } else {
            $tempPassword = $userModel->latestValidTempPassword((int) $user['id']);
            if ($tempPassword && password_verify($password, $tempPassword['temp_password_hash'])) {
                $userModel->consumeTempPassword((int) $tempPassword['id']);
                Session::set('force_password_change', true);
                Session::flash('success', 'Ingresaste con contraseña temporal. Debes cambiarla ahora.');
                $authenticated = true;
            }
        }

        if (!$authenticated) {
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        Auth::login($user);
        $userModel->touchLastLogin((int) $user['id']);
        AuditLogger::log('auth.login', 'user', (int) $user['id']);

        if ((bool) Session::get('force_password_change', false)) {
            $this->redirect('/profile/password');
        }
        $this->redirect('/');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        AuditLogger::log('auth.logout', 'user', Auth::user()['id'] ?? null);
        Auth::logout();
        Session::flash('success', 'Sesión cerrada correctamente.');
        $this->redirect('/login');
    }
}
