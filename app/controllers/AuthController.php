<?php

declare(strict_types=1);

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $expired = isset($_GET['expired']);

        $this->view('auth/login', [
            'title' => 'Iniciar sesión',
            'error' => $expired ? 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.' : Session::getFlash('error'),
            'success' => Session::getFlash('success'),
        ]);
    }

    public function login(): void
    {
        $this->requireCsrf('/login');

        if (Auth::check()) {
            $this->redirect('/');
        }

        $email = Sanitizer::email((string) $this->post('email'));
        $password = (string) $this->post('password');
        $ip = RateLimiter::getIp();

        // Validaciones básicas
        if ($email === '' || $password === '') {
            Session::flash('error', 'Correo y contraseña son obligatorios.');
            $this->redirect('/login');
        }

        // Verificar si está bloqueado por rate limiting
        if (RateLimiter::isBlocked($email, $ip)) {
            AuditLogger::log('auth.blocked', 'user', null, ['email' => $email, 'ip' => $ip]);
            Session::flash('error', 'Demasiados intentos fallidos. Espera 15 minutos antes de intentar de nuevo.');
            $this->redirect('/login');
        }

        // Detectar inputs sospechosos
        if (Sanitizer::isSuspicious($email) || Sanitizer::isSuspicious($password)) {
            AuditLogger::log('auth.suspicious', 'user', null, ['email' => $email, 'ip' => $ip]);
            Session::flash('error', 'Credenciales inválidas.');
            $this->redirect('/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !(bool) $user['is_active']) {
            RateLimiter::recordFailedLogin($email, $ip);
            AuditLogger::log('auth.failed', 'user', null, ['email' => $email, 'ip' => $ip]);
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
            RateLimiter::recordFailedLogin($email, $ip);
            $remaining = RateLimiter::remainingAttempts($email);
            AuditLogger::log('auth.failed', 'user', (int) $user['id'], ['ip' => $ip]);
            $msg = 'Credenciales inválidas.';
            if ($remaining <= 2 && $remaining > 0) {
                $msg .= " Te quedan {$remaining} intento(s) antes del bloqueo temporal.";
            }
            Session::flash('error', $msg);
            $this->redirect('/login');
        }

        // Login exitoso — limpiar intentos
        RateLimiter::clearAttempts($email, $ip);
        Auth::login($user);
        $userModel->touchLastLogin((int) $user['id']);
        AuditLogger::log('auth.login', 'user', (int) $user['id'], ['ip' => $ip]);

        if ((bool) Session::get('force_password_change', false)) {
            $this->redirect('/profile/password');
        }

        $this->redirect('/');
    }

    public function logout(): void
    {
        $this->requireCsrf('/');

        if (Auth::check()) {
            AuditLogger::log('auth.logout', 'user', Auth::user()['id'] ?? null);
        }

        Auth::logout();

        session_name((require CONFIG_PATH . '/app.php')['session_name'] ?? 'app_session');
        session_start();
        Session::flash('success', 'Sesión cerrada correctamente.');

        $this->redirect('/login');
    }
}
