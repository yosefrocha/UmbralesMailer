<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Destinatarios</h1>
        <p class="text-muted mb-0">Gestión manual e importación por CSV.</p>
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
                        <input
                            type="text"
                            class="form-control"
                            name="q"
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="correo, nombre, institución..."
                        >
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

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-outline-primary w-100">Filtrar</button>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <a href="/recipients" class="btn btn-outline-secondary">Limpiar filtros</a>
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
                            <a href="/recipients/template" class="btn btn-outline-secondary">
                                Plantilla CSV
                            </a>
                        </div>
                    </form>

                    <?php if (!empty($importResult)): ?>
                        <hr>
                        <div class="small">
                            <div><strong>Total:</strong> <?= (int) $importResult['total'] ?></div>
                            <div><strong>Importados:</strong> <?= (int) $importResult['imported'] ?></div>
                            <div><strong>Fallidos:</strong> <?= (int) $importResult['failed'] ?></div>
                        </div>
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
                    <th>Correo</th>
                    <th>Nombre</th>
                    <th>Institución</th>
                    <th>Segmento</th>
                    <th>Estado</th>
                    <th>Suscripción</th>
                    <?php if (Auth::isAdmin()): ?>
                        <th class="text-end">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($recipients)): ?>
                <?php foreach ($recipients as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= !empty($row['unsubscribed_at']) ? 'Desuscrito' : 'Activo' ?></td>
                        <?php if (Auth::isAdmin()): ?>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="/recipients/<?= (int) $row['id'] ?>/edit">Editar</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= Auth::isAdmin() ? 7 : 6 ?>" class="text-center text-muted py-4">
                        No se encontraron destinatarios.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>