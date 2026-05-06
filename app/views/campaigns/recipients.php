<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Destinatarios de campaña</h1>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?> · Asignados: <?= (int) $assignedCount ?>
        </p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<?php
$activeTab = (string)($_GET['tab'] ?? 'assigned');
if (!in_array($activeTab, ['assigned', 'assign', 'import'], true)) {
    $activeTab = 'assigned';
}
$search = (string)($search ?? '');
$segment = (string)($segment ?? '');
$country = (string)($country ?? '');
$institution = (string)($institution ?? '');
$segments = $segments ?? [];
$countries = $countries ?? [];
$institutions = $institutions ?? [];
?>

<ul class="nav nav-tabs mb-4" id="recipientTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'assigned' ? 'active' : '' ?>" id="tab-assigned-btn" data-bs-toggle="tab" data-bs-target="#tab-assigned" type="button" role="tab" aria-controls="tab-assigned" aria-selected="<?= $activeTab === 'assigned' ? 'true' : 'false' ?>">
            Asignados (<?= (int) $assignedCount ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'assign' ? 'active' : '' ?>" id="tab-assign-btn" data-bs-toggle="tab" data-bs-target="#tab-assign" type="button" role="tab" aria-controls="tab-assign" aria-selected="<?= $activeTab === 'assign' ? 'true' : 'false' ?>">
            Asignar existentes
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $activeTab === 'import' ? 'active' : '' ?>" id="tab-import-btn" data-bs-toggle="tab" data-bs-target="#tab-import" type="button" role="tab" aria-controls="tab-import" aria-selected="<?= $activeTab === 'import' ? 'true' : 'false' ?>">
            Importar CSV
        </button>
    </li>
</ul>

<div class="tab-content" id="recipientTabsContent">

    <div class="tab-pane fade <?= $activeTab === 'assigned' ? 'show active' : '' ?>" id="tab-assigned" role="tabpanel" aria-labelledby="tab-assigned-btn">
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
                            <?php if (true): ?>
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
                                <td><?= htmlspecialchars((string)($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($row['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($row['campaign_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if (true): ?>
                                    <td class="text-end">
                                        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/<?= (int) $row['id'] ?>/remove" onsubmit="return confirm('¿Quitar este destinatario?');">
                                            <?= Csrf::input() ?>
                                            <button class="btn btn-sm btn-outline-danger">Quitar</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay destinatarios asignados.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'assign' ? 'show active' : '' ?>" id="tab-assign" role="tabpanel" aria-labelledby="tab-assign-btn">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Filtrar destinatarios disponibles</h2>
                <form method="get" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients" class="row g-2">
                    <input type="hidden" name="tab" value="assign">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar correo, nombre o institución...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="segment">
                            <option value="">Todos los segmentos</option>
                            <?php foreach ($segments as $seg): ?>
                                <option value="<?= htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') ?>" <?= $segment === $seg ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="country">
                            <option value="">Todos los países</option>
                            <?php foreach ($countries as $c): ?>
                                <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>" <?= $country === $c ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="institution">
                            <option value="">Todas las instituciones</option>
                            <?php foreach ($institutions as $inst): ?>
                                <option value="<?= htmlspecialchars($inst, ENT_QUOTES, 'UTF-8') ?>" <?= $institution === $inst ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($inst, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (true && !empty($availableRecipients)): ?>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/assign-bulk" onsubmit="return confirm('¿Asignar todos los destinatarios visibles?');">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="segment" value="<?= htmlspecialchars($segment, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="country" value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="institution" value="<?= htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-sm btn-warning">
                        Asignar todos (<?= count($availableRecipients) ?>)
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Correo</th>
                            <th>Nombre</th>
                            <th>Institución</th>
                            <th>Segmento</th>
                            <th>País</th>
                            <?php if (true): ?>
                                <th class="text-end">Asignar</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($availableRecipients)): ?>
                        <?php foreach ($availableRecipients as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($row['segment'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($row['country'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if (true): ?>
                                    <td class="text-end">
                                        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/assign">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="recipient_id" value="<?= (int) $row['id'] ?>">
                                            <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="segment" value="<?= htmlspecialchars($segment, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="country" value="<?= htmlspecialchars($country, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="institution" value="<?= htmlspecialchars($institution, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="btn btn-sm btn-outline-success">+ Asignar</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay destinatarios disponibles con este filtro.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-pane fade <?= $activeTab === 'import' ? 'show active' : '' ?>" id="tab-import" role="tabpanel" aria-labelledby="tab-import-btn">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6 mb-1">Importar CSV a la campaña</h2>
                        <p class="small text-muted mb-3">Columnas requeridas: correo, nombre, apellido, inst, pais, segmento, estado, consent</p>
                        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/recipients/import" enctype="multipart/form-data">
                            <?= Csrf::input() ?>
                            <input type="file" class="form-control mb-3" name="csv" accept=".csv" required>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-outline-primary btn-sm" formaction="/campaigns/<?= (int) $campaign['id'] ?>/recipients/validate">Validar sin guardar</button>
                                <button type="submit" class="btn btn-primary btn-sm">Importar y asignar</button>
                                <a href="/campaigns/<?= (int) $campaign['id'] ?>/recipients/template" class="btn btn-outline-secondary btn-sm">Descargar plantilla</a>
                            </div>
                        </form>

                        <?php if (!empty($importResult)): ?>
                            <hr>
                            <div class="small">
                                <div><strong>Total procesados:</strong> <?= (int)$importResult['total'] ?></div>
                                <div class="text-success"><strong>Importados/asignados:</strong> <?= (int)$importResult['imported'] ?></div>
                                <?php if (isset($importResult['created'])): ?>
                                    <div><strong>Nuevos:</strong> <?= (int)$importResult['created'] ?></div>
                                <?php endif; ?>
                                <?php if (isset($importResult['updated'])): ?>
                                    <div><strong>Fusionados/actualizados:</strong> <?= (int)$importResult['updated'] ?></div>
                                <?php endif; ?>
                                <div class="text-danger"><strong>Fallidos:</strong> <?= (int)$importResult['failed'] ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($validationResult)): ?>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">Resultado de validación</h2>
                        <div class="small">
                            <div class="text-success mb-1"><strong>Válidos:</strong> <?= (int)$validationResult['valid'] ?></div>
                            <div class="text-danger mb-2"><strong>Con errores:</strong> <?= count($validationResult['errors']) ?></div>
                            <?php if (!empty($validationResult['errors'])): ?>
                                <ul class="ps-3 mb-0">
                                    <?php foreach (array_slice($validationResult['errors'], 0, 20) as $err): ?>
                                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($validationResult['errors']) > 20): ?>
                                        <li>... y <?= count($validationResult['errors']) - 20 ?> más.</li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-success">✓ El archivo está listo para importar.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const activeTab = params.get('tab') || 'assigned';
    const tabMap = {
        assigned: 'tab-assigned-btn',
        assign: 'tab-assign-btn',
        import: 'tab-import-btn'
    };

    const tabEl = document.getElementById(tabMap[activeTab] || 'tab-assigned-btn');
    if (tabEl && window.bootstrap && window.bootstrap.Tab) {
        new bootstrap.Tab(tabEl).show();
    }

    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target') || '#tab-assigned';
            const tabName = target.replace('#tab-', '');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        });
    });
})();
</script>
