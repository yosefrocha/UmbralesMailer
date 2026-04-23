<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Destinatarios de campaña</h1>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?> · Asignados: <?= (int) $assignedCount ?>
        </p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6">Importar CSV a campaña</h2>
                <p class="small text-muted mb-2">Plantilla esperada: correo, nombre, apellido, inst, pais, segmento, estado, consent</p>

                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/import" enctype="multipart/form-data">
                    <?= Csrf::input() ?>
                    <input type="file" class="form-control mb-3" name="csv" accept=".csv" required>

                    <div class="d-flex gap-2 flex-wrap">
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

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Correo</th>
                            <th>Nombre</th>
                            <th>Institución</th>
                            <th>Estado</th>
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
                                <td><?= htmlspecialchars((string) ($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if (Auth::isAdmin()): ?>
                                    <td class="text-end">
                                        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/<?= (int) $row['id'] ?>/remove" onsubmit="return confirm('¿Quitar este destinatario de la campaña?');">
                                            <?= Csrf::input() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= Auth::isAdmin() ? 5 : 4 ?>" class="text-center text-muted py-4">
                                No hay destinatarios asignados a esta campaña.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>