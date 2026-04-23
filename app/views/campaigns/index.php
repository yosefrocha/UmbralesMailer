<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Campañas</h1>
        <p class="text-muted mb-0">Gestiona campañas y mensajes de envío.</p>
    </div>
    <a href="/campaigns/create" class="btn btn-primary">Nueva campaña</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Creó</th>
                    <th>Sesiones</th>
                    <th>Última actividad</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($campaigns)): ?>
                <?php foreach ($campaigns as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                                $status = (string) ($row['status'] ?? 'draft');
                                $labels = [
                                    'draft' => 'Borrador',
                                    'active' => 'Activa',
                                    'inactive' => 'Inactiva',
                                    'cancelled' => 'Cancelada',
                                    'completed' => 'Completada',
                                    'failed' => 'Fallida',
                                    'processing' => 'En proceso',
                                ];
                            ?>
                            <?= htmlspecialchars($labels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                                                <td><?= htmlspecialchars($row['creator_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $row['sessions_count'] ?></td>
                        <td><?= htmlspecialchars((string) ($row['last_activity_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                <a href="/campaigns/<?= (int) $row['id'] ?>" class="btn btn-sm btn-outline-primary">Abrir</a>

                                <?php if ($row['status'] === 'active'): ?>
                                    <form method="post" action="/campaigns/<?= (int) $row['id'] ?>/deactivate" class="d-inline">
                                        <?= Csrf::input() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Desactivar</button>
                                    </form>
                                <?php elseif (in_array($row['status'], ['draft', 'inactive'], true)): ?>
                                    <form method="post" action="/campaigns/<?= (int) $row['id'] ?>/activate" class="d-inline">
                                        <?= Csrf::input() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-success">Activar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No hay campañas registradas.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>