<?php
$filters = $filters ?? [
    'search' => '',
    'status' => '',
    'per_page' => 20,
    'page' => 1,
    'total' => count($campaigns ?? []),
    'total_pages' => 1,
    'from' => count($campaigns ?? []) ? 1 : 0,
    'to' => count($campaigns ?? []),
];
$statusLabels = [
    '' => 'Todos los estados',
    'draft' => 'Borrador',
    'active' => 'Activa',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
];
$buildUrl = static function (array $overrides = []) use ($filters): string {
    $params = [
        'q' => (string) ($filters['search'] ?? ''),
        'status' => (string) ($filters['status'] ?? ''),
        'page' => (int) ($filters['page'] ?? 1),
    ];
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '/campaigns' . (empty($params) ? '' : '?' . http_build_query($params));
};
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Campañas</h1>
    </div>
    <a href="/campaigns/create" class="btn btn-primary">Nueva campaña</a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-3 align-items-end" method="get" action="/campaigns">
            <div class="col-lg-6">
                <label class="form-label">Buscar campaña</label>
                <input type="text" name="q" class="form-control" placeholder="Nombre o descripción" value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-lg-3">
                <label class="form-label">Estado</label>
                <select name="status" class="form-select">
                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 d-flex gap-2 align-items-end">
                <input type="hidden" name="page" value="1">
                <button type="submit" class="btn btn-outline-primary flex-fill">Filtrar</button>
                <a href="/campaigns" class="btn btn-outline-secondary flex-fill">Borrar filtros</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Campaña</th>
                    <th>Estado</th>
                    <th>Creada por</th>
                    <th class="text-end">Destinatarios</th>
                    <th>Última actividad</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string) $campaign['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($statusLabels[$campaign['status']] ?? $campaign['status']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($campaign['creator_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= (int) ($campaign['recipients_count'] ?? 0) ?></td>
                        <td><?= htmlspecialchars((string) ($campaign['last_activity_at'] ?? $campaign['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-sm btn-outline-primary">Abrir</a>
                            <?php if (($campaign['status'] ?? '') === 'active'): ?>
                                <form class="d-inline" method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/deactivate">
                                    <?= Csrf::input() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-warning">Desactivar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($campaigns)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No hay campañas que coincidan con la búsqueda.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ((int) ($filters['total_pages'] ?? 1) > 1): ?>
        <div class="card-footer bg-white">
            <nav aria-label="Paginación de campañas">
                <ul class="pagination justify-content-end flex-wrap mb-0">
                    <?php $page = (int) $filters['page']; $totalPages = (int) $filters['total_pages']; ?>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars($buildUrl(['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a></li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($buildUrl(['page' => $i]), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page >= $totalPages ? '#' : htmlspecialchars($buildUrl(['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>">Siguiente</a></li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
