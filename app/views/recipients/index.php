<?php
$pagination = $pagination ?? [
    'page' => 1,
    'per_page' => 25,
    'total' => count($recipients ?? []),
    'total_pages' => 1,
    'from' => count($recipients ?? []) ? 1 : 0,
    'to' => count($recipients ?? []),
];
$sort = $sort ?? 'created_at';
$direction = $direction ?? 'desc';
$buildUrl = static function (array $overrides = []) use ($search, $statusFilter, $sort, $direction): string {
    $params = [
        'q' => (string) ($search ?? ''),
        'status' => (string) ($statusFilter ?? ''),
        'sort' => (string) $sort,
        'dir' => (string) $direction,
        'page' => 1,
    ];
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return '/recipients' . (empty($params) ? '' : '?' . http_build_query($params));
};
$sortUrl = static function (string $column) use ($buildUrl, $sort, $direction): string {
    $nextDir = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
    return $buildUrl(['sort' => $column, 'dir' => $nextDir, 'page' => 1]);
};
$sortIcon = static function (string $column) use ($sort, $direction): string {
    if ($sort !== $column) {
        return '↕';
    }
    return $direction === 'asc' ? '↑' : '↓';
};
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Destinatarios</h1>
    </div>
    <?php if (Auth::isAdmin()): ?>
        <a href="/recipients/create" class="btn btn-primary">Nuevo destinatario</a>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form class="row g-3" method="get" action="/recipients">
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="correo, nombre, institución, país, segmento...">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="status">
                            <option value="">Todos</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Activos</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactivos</option>
                            <option value="subscribed" <?= $statusFilter === 'subscribed' ? 'selected' : '' ?>>Suscritos</option>
                            <option value="unsubscribed" <?= $statusFilter === 'unsubscribed' ? 'selected' : '' ?>>Desuscritos</option>
                        </select>
                    </div>

                    <input type="hidden" name="sort" value="<?= htmlspecialchars((string) $sort, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="dir" value="<?= htmlspecialchars((string) $direction, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="page" value="1">

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <a href="/recipients" class="btn btn-outline-secondary">Borrar filtros</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if (Auth::isAdmin()): ?>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Importar CSV</h2>
                    <form method="post" action="/recipients/import" enctype="multipart/form-data">
                        <?= Csrf::input() ?>
                        <input type="file" class="form-control mb-3" name="csv" accept=".csv" required>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary">Importar</button>
                            <a href="/recipients/template" class="btn btn-outline-secondary">Plantilla CSV</a>
                        </div>
                    </form>

                    <?php if (!empty($importResult)): ?>
                        <hr>
                        <div class="small"><div><strong>Total:</strong> <?= (int) $importResult['total'] ?></div><div><strong>Importados:</strong> <?= (int) $importResult['imported'] ?></div><div><strong>Fallidos:</strong> <?= (int) $importResult['failed'] ?></div></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('email'), ENT_QUOTES, 'UTF-8') ?>">Correo <?= $sortIcon('email') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('first_name'), ENT_QUOTES, 'UTF-8') ?>">Nombre <?= $sortIcon('first_name') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('institution'), ENT_QUOTES, 'UTF-8') ?>">Institución <?= $sortIcon('institution') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('country'), ENT_QUOTES, 'UTF-8') ?>">País <?= $sortIcon('country') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('segment'), ENT_QUOTES, 'UTF-8') ?>">Segmento <?= $sortIcon('segment') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('status'), ENT_QUOTES, 'UTF-8') ?>">Estado <?= $sortIcon('status') ?></a></th>
                    <th><a class="text-decoration-none text-dark" href="<?= htmlspecialchars($sortUrl('subscription'), ENT_QUOTES, 'UTF-8') ?>">Suscripción <?= $sortIcon('subscription') ?></a></th>
                    <?php if (Auth::isAdmin()): ?><th class="text-end">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($recipients)): ?>
                <?php foreach ($recipients as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= !empty($row['unsubscribed_at']) ? 'Desuscrito' : 'Activo' ?></td>
                        <?php if (Auth::isAdmin()): ?>
                            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="/recipients/<?= (int) $row['id'] ?>/edit">Editar</a></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="<?= Auth::isAdmin() ? 8 : 7 ?>" class="text-center text-muted py-4">No se encontraron destinatarios.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ((int) ($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="card-footer bg-white">
            <nav aria-label="Paginación de destinatarios">
                <ul class="pagination justify-content-end flex-wrap mb-0">
                    <?php $page = (int) $pagination['page']; $totalPages = (int) $pagination['total_pages']; ?>
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
