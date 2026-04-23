<?php

declare(strict_types=1);

final class UsersController extends Controller
{
    public function index(): void
    {
        $this->view('users/index', [
            'title' => 'Usuarios',
            'users' => (new User())->all(),
            'error' => Session::getFlash('error'),
            'success' => Session::getFlash('success'),
            'tempPassword' => Session::getFlash('temp_password'),
        ]);
    }

    public function create(): void
    {
        $this->view('users/form', [
            'title' => 'Crear usuario',
            'userData' => null,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $data = $this->validate();
        $userModel = new User();
        if ($userModel->findByEmail($data['email'])) {
            Session::flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('/users/create');
        }
        $id = $userModel->create($data);
        AuditLogger::log('user.created', 'user', $id, $data);
        Session::flash('success', 'Usuario creado correctamente.');
        $this->redirect('/users');
    }

    public function edit(string $id): void
    {
        $user = (new User())->find($this->intId($id));
        if (!$user) {
            $this->redirect('/users');
        }
        $this->view('users/form', [
            'title' => 'Editar usuario',
            'userData' => $user,
            'error' => Session::getFlash('error'),
        ]);
    }

    public function update(string $id): void
    {
        $this->requireCsrf();
        $userId = $this->intId($id);
        $data = $this->validate(true);
        (new User())->updateUser($userId, $data);
        AuditLogger::log('user.updated', 'user', $userId, $data);
        Session::flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('/users');
    }

    public function toggle(string $id): void
    {
        $this->requireCsrf();
        $userId = $this->intId($id);
        if ((int) (Auth::user()['id'] ?? 0) === $userId) {
            Session::flash('error', 'No puedes desactivar tu propia cuenta.');
            $this->redirect('/users');
        }
        (new User())->toggleActive($userId);
        AuditLogger::log('user.toggled', 'user', $userId);
        Session::flash('success', 'Estado del usuario actualizado.');
        $this->redirect('/users');
    }

    public function generateTempPassword(string $id): void
    {
        $this->requireCsrf();
        $userId = $this->intId($id);
        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            $this->redirect('/users');
        }
        $temp = PasswordService::generateTemporary();
        $userModel->createTempPassword($userId, $temp, 30);
        AuditLogger::log('user.temp_password.generated', 'user', $userId);
        Session::flash('success', 'Contraseña temporal generada. Compártela de forma segura. Vigencia: 30 minutos.');
        Session::flash('temp_password', ['email' => $user['email'], 'password' => $temp]);
        $this->redirect('/users');
    }

    private function validate(bool $isUpdate = false): array
    {
        $name = trim((string) $this->post('name'));
        $email = trim((string) $this->post('email'));
        $password = (string) $this->post('password');
        $role = (string) $this->post('role');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Nombre y correo válidos son obligatorios.');
            $this->redirect($isUpdate ? $_SERVER['REQUEST_URI'] : '/users/create');
        }
        if (!$isUpdate && strlen($password) < 8) {
            Session::flash('error', 'La contraseña debe tener al menos 8 caracteres.');
            $this->redirect('/users/create');
        }
        return [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => in_array($role, ['admin', 'user'], true) ? $role : 'user',
            'is_active' => $this->post('is_active') === '1',
        ];
    }
}
