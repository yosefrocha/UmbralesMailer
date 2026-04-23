<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Usuarios</h1>
        <p class="text-muted mb-0">Administración de accesos y roles.</p>
    </div>
    <a href="/users/create" class="btn btn-primary">Nuevo usuario</a>
</div>

<?php if (!empty($tempPassword)): ?>
    <div class="alert alert-warning">
        Contraseña temporal generada para <strong><?= htmlspecialchars($tempPassword['email'], ENT_QUOTES, 'UTF-8') ?></strong>: 
        <code><?= htmlspecialchars($tempPassword['password'], ENT_QUOTES, 'UTF-8') ?></code>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th>Último acceso</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $row['is_active'] === 1 ? 'Sí' : 'No' ?></td>
                    <td><?= htmlspecialchars((string) ($row['last_login_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="/users/<?= (int) $row['id'] ?>/edit">Editar</a>
                            <form method="post" action="/users/<?= (int) $row['id'] ?>/toggle">
                                <?= Csrf::input() ?>
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Activar / desactivar</button>
                            </form>
                            <form method="post" action="/users/<?= (int) $row['id'] ?>/temp-password">
                                <?= Csrf::input() ?>
                                <button type="submit" class="btn btn-sm btn-outline-warning">Contraseña temporal</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
