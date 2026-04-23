<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0">Completa los datos del usuario.</p>
    </div>
    <a href="/users" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= $userData ? '/users/' . (int) $userData['id'] . '/update' : '/users/store' ?>">
            <?= Csrf::input() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars((string) ($userData['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Correo</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string) ($userData['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rol</label>
                    <select class="form-select" name="role">
                        <option value="user" <?= (($userData['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Usuario</option>
                        <option value="admin" <?= (($userData['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contraseña <?= $userData ? '(dejar vacía para conservar)' : '' ?></label>
                    <input type="password" class="form-control" name="password" <?= $userData ? '' : 'required' ?>>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Activo</label>
                    <select class="form-select" name="is_active">
                        <option value="1" <?= ((int) ($userData['is_active'] ?? 1) === 1) ? 'selected' : '' ?>>Sí</option>
                        <option value="0" <?= ((int) ($userData['is_active'] ?? 1) === 0) ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>
