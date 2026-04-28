<?php
$totals = $dashboardTotals ?? [];
$performance = $campaignPerformance ?? [];
$maxSent = 1;
foreach ($performance as $row) {
    $maxSent = max($maxSent, (int) ($row['sent_count'] ?? 0));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h1 class="h3 mb-1">Panel principal</h1>
        <p class="text-muted mb-0">Métricas reales tomadas de campañas, sesiones de envío y destinatarios.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Enviados</div>
            <div class="metric-value"><?= (int) ($totals['sent'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Abiertos</div>
            <div class="metric-value"><?= (int) ($totals['opened'] ?? 0) ?></div>
            <div class="small text-muted">Mensajes abiertos</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Rebotes / errores</div>
            <div class="metric-value"><?= (int) ($totals['bounced'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Bajas</div>
            <div class="metric-value"><?= (int) ($totals['unsubscribed'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Destinatarios activos</div>
            <div class="metric-value"><?= (int) ($totals['active_recipients'] ?? $activeRecipients ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Campañas</div>
            <div class="metric-value"><?= (int) ($totals['campaigns'] ?? $campaignsCount ?? 0) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Rendimiento por campaña</h2>
                        <p class="text-muted small mb-0">Comparativo de envíos, aperturas, rebotes y bajas.</p>
                    </div>
                    <a href="/campaigns" class="btn btn-sm btn-outline-primary">Ver campañas</a>
                </div>

                <?php if (!empty($performance)): ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($performance as $row): ?>
                            <?php
                                $sent = (int) ($row['sent_count'] ?? 0);
                                $opened = (int) ($row['opened_count'] ?? 0);                                $width = $maxSent > 0 ? (int) round(($sent / $maxSent) * 100) : 0;
                            ?>
                            <div class="bar-row">
                                <div class="small fw-semibold text-truncate">
                                    <a href="/campaigns/<?= (int) $row['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </div>
                                <div class="bar-track" title="<?= $sent ?> enviados · <?= $opened ?> abiertos únicos">
                                    <div class="bar-fill" style="width: <?= max(3, $width) ?>%;"></div>
                                </div>
                                <div class="small text-muted text-end"><?= $sent ?> env. · <?= $opened ?> ab.</div>
                                <div class="small text-muted d-md-none">
                                    Abiertos: <?= $opened ?> · Rebotes: <?= (int) ($row['bounced_count'] ?? 0) ?> · Bajas: <?= (int) ($row['unsubscribed_count'] ?? 0) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Aún no hay campañas con datos para graficar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Última sesión de envío</h2>
                <?php if (!empty($latestSession)): ?>
                    <?php
                        $total = (int) ($latestSession['total_count'] ?? 0);
                        $processed = (int) ($latestSession['processed_count'] ?? 0);
                        $progress = $total > 0 ? (int) round(($processed / $total) * 100) : 0;
                    ?>
                    <p class="mb-1"><strong>ID:</strong> #<?= (int) $latestSession['id'] ?></p>
                    <p class="mb-1"><strong>Estado:</strong> <?= htmlspecialchars((string) $latestSession['status'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-2"><strong>Procesados:</strong> <?= $processed ?> / <?= $total ?></p>
                    <div class="progress mb-3" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: <?= $progress ?>%;"><?= $progress ?>%</div>
                    </div>
                    <a href="/sending/<?= (int) $latestSession['id'] ?>" class="btn btn-sm btn-primary">Abrir monitoreo</a>
                <?php else: ?>
                    <p class="text-muted mb-0">Aún no hay sesiones de envío registradas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Detalle por campaña</h2>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Campaña</th>
                        <th>Estado</th>
                        <th class="text-end">Asignados</th>
                        <th class="text-end">Enviados</th>
                        <th class="text-end">Abiertos</th>
                        <th class="text-end">Rebotes</th>
                        <th class="text-end">Bajas</th>
                        <th class="text-end">Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($performance as $row): ?>
                        <tr>
                            <td><a href="/campaigns/<?= (int) $row['id'] ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td><?= htmlspecialchars((string) $row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end"><?= (int) ($row['assigned_count'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($row['sent_count'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($row['opened_count'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($row['bounced_count'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($row['unsubscribed_count'] ?? 0) ?></td>
                            <td class="text-end"><?= (int) ($row['progress_percent'] ?? 0) ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($performance)): ?>
                        <tr><td colspan="8" class="text-muted">No hay campañas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
