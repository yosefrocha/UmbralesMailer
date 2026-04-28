<?php
$rows = $rows ?? [];
$summary = $summary ?? [];
$filter = (string) ($filter ?? 'all');
$search = (string) ($search ?? '');
$campaignId = (int) ($campaign['id'] ?? 0);
$filterLabels = [
    'all' => 'Todos',
    'opened' => 'Abrieron',
    'not_opened' => 'No abrieron',
    'failed' => 'Fallidos',
    'not_sent' => 'No enviados',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h1 class="h3 mb-1">Aperturas de campaña</h1>
        <p class="text-muted mb-0"><?= htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap page-actions">
        <a href="/campaigns/<?= $campaignId ?>" class="btn btn-outline-secondary">Volver a campaña</a>
        <a href="/campaigns/<?= $campaignId ?>/recipients" class="btn btn-outline-primary">Destinatarios</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Enviados</div>
            <div class="metric-value"><?= (int) ($summary['sent'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Abrieron</div>
            <div class="metric-value"><?= (int) ($summary['opened'] ?? 0) ?></div>
            <div class="small text-muted"><?= (int) ($summary['open_rate'] ?? 0) ?>% apertura</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">No abrieron</div>
            <div class="metric-value"><?= (int) ($summary['not_opened'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Fallidos</div>
            <div class="metric-value"><?= (int) ($summary['failed'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">No enviados</div>
            <div class="metric-value"><?= (int) ($summary['not_sent'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Destinatarios</div>
            <div class="metric-value"><?= (int) ($summary['total'] ?? 0) ?></div>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Correo, nombre, institución o segmento">
            </div>
            <div class="col-md-4">
                <label class="form-label">Filtro</label>
                <select name="filter" class="form-select">
                    <?php foreach ($filterLabels as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filter === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Aplicar</button>
                <a href="/campaigns/<?= $campaignId ?>/opens" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Quién abrió y quién no</h2>
                <p class="text-muted small mb-0">La columna “Abrió” muestra si el destinatario cargó el correo al menos una vez.</p>
            </div>
            <span class="badge text-bg-light border"><?= count($rows) ?> registros visibles</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Correo</th>
                        <th>Nombre</th>
                        <th>Institución</th>
                        <th>Segmento</th>
                        <th>Envío</th>
                        <th>Abrió</th>
                        <th>Fecha de apertura</th>
                        <th>Procesado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $opened = !empty($row['opened']);
                            $sendStatus = (string) ($row['send_status'] ?? 'not_sent');
                            $sendBadge = [
                                'sent' => 'text-bg-success',
                                'failed' => 'text-bg-danger',
                                'pending' => 'text-bg-warning',
                                'skipped' => 'text-bg-secondary',
                                'not_sent' => 'text-bg-light border text-dark',
                            ][$sendStatus] ?? 'text-bg-light border text-dark';
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($row['last_error'])): ?>
                                    <div class="small text-danger"><?= htmlspecialchars((string) $row['last_error'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($row['name'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['institution'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['segment'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><span class="badge <?= $sendBadge ?>"><?= htmlspecialchars((string) ($row['send_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td>
                                <?php if ($opened): ?>
                                    <span class="badge text-bg-success">Sí</span>
                                <?php elseif ($sendStatus === 'sent'): ?>
                                    <span class="badge text-bg-secondary">No</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($row['opened_at']) ? htmlspecialchars((string) $row['opened_at'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                            <td><?= !empty($row['last_processed_at']) ? htmlspecialchars((string) $row['last_processed_at'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-muted">No hay destinatarios con ese filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
