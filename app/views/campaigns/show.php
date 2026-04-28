<?php
$status = (string) ($campaign['status'] ?? 'draft');
$labels = [
    'draft' => 'Borrador',
    'active' => 'Activa',
    'inactive' => 'Inactiva',
    'cancelled' => 'Cancelada',
    'completed' => 'Completada',
    'failed' => 'Fallida',
    'processing' => 'En proceso',
    'paused' => 'Pausada',
];
$metrics = $campaignMetrics ?? [];
$timeline = $campaignTimeline ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="h3 mb-0"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></h1>
            <span class="badge rounded-pill text-bg-light border">
                <span class="status-dot status-<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"></span>
                <?= htmlspecialchars($labels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <p class="text-muted mb-0"><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap page-actions">
        <a href="/campaigns" class="btn btn-outline-secondary">Volver</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/edit" class="btn btn-outline-primary">Editar</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-primary">Mensaje</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/recipients" class="btn btn-outline-primary">Destinatarios</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/opens" class="btn btn-outline-success">Aperturas</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/schedule" class="btn btn-outline-dark">Envío programado</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-2">
            <div>
                <h2 class="h5 mb-1">Progreso de la campaña</h2>
                <p class="text-muted small mb-0">
                    <?= (int) ($metrics['processed'] ?? 0) ?> procesados de <?= (int) max((int) ($metrics['assigned'] ?? 0), (int) ($metrics['total_items'] ?? 0)) ?> registros preparados.
                </p>
            </div>
            <div class="display-6 fw-bold"><?= (int) ($metrics['progress_percent'] ?? 0) ?>%</div>
        </div>
        <div class="progress" style="height: 1rem;" role="progressbar" aria-valuenow="<?= (int) ($metrics['progress_percent'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= (int) ($metrics['progress_percent'] ?? 0) ?>%;"></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Asignados</div>
            <div class="metric-value"><?= (int) ($metrics['assigned'] ?? $activeRecipients ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Enviados</div>
            <div class="metric-value"><?= (int) ($metrics['sent'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Abiertos</div>
            <div class="metric-value"><?= (int) ($metrics['opened'] ?? 0) ?></div>
            <div class="small text-muted"><?= (int) ($metrics['open_rate'] ?? 0) ?>% apertura</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Rebotes / errores</div>
            <div class="metric-value"><?= (int) ($metrics['bounced'] ?? 0) ?></div>
            <div class="small text-muted"><?= (int) ($metrics['bounce_rate'] ?? 0) ?>% error</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Bajas</div>
            <div class="metric-value"><?= (int) ($metrics['unsubscribed'] ?? 0) ?></div>
            <div class="small text-muted"><?= (int) ($metrics['unsubscribe_rate'] ?? 0) ?>% baja</div>
        </div></div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card metric-card h-100"><div class="card-body">
            <div class="metric-label">Pendientes</div>
            <div class="metric-value"><?= (int) ($metrics['pending'] ?? 0) ?></div>
        </div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Gráfica de rendimiento</h2>
                <?php
                    $bars = [
                        'Enviados' => (int) ($metrics['sent'] ?? 0),
                        'Abiertos' => (int) ($metrics['opened'] ?? 0),
                        'Rebotes' => (int) ($metrics['bounced'] ?? 0),
                        'Bajas' => (int) ($metrics['unsubscribed'] ?? 0),
                    ];
                    $max = max(1, ...array_values($bars));
                ?>
                <div class="d-grid gap-3">
                    <?php foreach ($bars as $label => $value): ?>
                        <div class="bar-row">
                            <div class="small fw-semibold"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="bar-track">
                                <div class="bar-fill" style="width: <?= max(2, (int) round(($value / $max) * 100)) ?>%;"></div>
                            </div>
                            <div class="small text-muted text-end"><?= $value ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Última actividad</h2>
                <?php if (!empty($latestSession)): ?>
                    <p class="mb-1"><strong>Sesión:</strong> #<?= (int) $latestSession['id'] ?></p>
                    <p class="mb-1"><strong>Estado:</strong> <?= htmlspecialchars((string) $latestSession['status'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-1"><strong>Éxitos:</strong> <?= (int) ($latestSession['success_count'] ?? 0) ?></p>
                    <p class="mb-1"><strong>Fallidos:</strong> <?= (int) ($latestSession['failed_count'] ?? 0) ?></p>
                    <p class="mb-3"><strong>Actualizado:</strong> <?= htmlspecialchars((string) ($latestSession['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="/sending/<?= (int) $latestSession['id'] ?>" class="btn btn-sm btn-primary">Abrir monitoreo</a>
                <?php else: ?>
                    <p class="text-muted mb-0">Esta campaña todavía no tiene sesiones de envío.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Mensaje configurado</h2>

                <?php if ($message): ?>
                    <p class="mb-1"><strong>Asunto:</strong> <?= htmlspecialchars((string) $message['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="mb-1">
                        <strong>Remitente:</strong>
                        <?= htmlspecialchars(((string) ($message['from_name'] ?? '')) . ' <' . ((string) ($message['from_email'] ?? '')) . '>', ENT_QUOTES, 'UTF-8') ?>
                    </p>
                    <p class="mb-0">
                        <strong>Modo:</strong>
                        <?= (($campaign['content_mode'] ?? 'text') === 'html') ? 'HTML' : 'Texto plano' ?>
                    </p>
                <?php else: ?>
                    <p class="text-muted mb-0">Aún no has configurado el mensaje de esta campaña.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5">Acciones</h2>

                <div class="d-grid gap-2">
                    <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-outline-primary">
                        Editar mensaje
                    </a>

                    <a href="/campaigns/<?= (int) $campaign['id'] ?>/recipients" class="btn btn-outline-primary">
                        Gestionar destinatarios
                    </a>

                    <a href="/campaigns/<?= (int) $campaign['id'] ?>/opens" class="btn btn-outline-success">
                        Ver quién abrió
                    </a>

                    <a href="/campaigns/<?= (int) $campaign['id'] ?>/schedule" class="btn btn-outline-dark">
                        Envío programado
                    </a>

                    <?php if ($campaign['status'] === 'active'): ?>
                        <a href="/campaigns/<?= (int) $campaign['id'] ?>/send" class="btn btn-primary <?= $message ? '' : 'disabled' ?>">
                            Preparar envío
                        </a>
                    <?php endif; ?>

                    <?php if (Auth::isAdmin() && $campaign['status'] !== 'cancelled'): ?>
                        <?php if ($campaign['status'] === 'active'): ?>
                            <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/deactivate">
                                <?= Csrf::input() ?>
                                <button type="submit" class="btn btn-warning w-100">Desactivar campaña</button>
                            </form>
                        <?php elseif (in_array($campaign['status'], ['draft', 'inactive'], true)): ?>
                            <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/activate">
                                <?= Csrf::input() ?>
                                <button type="submit" class="btn btn-success w-100">Activar campaña</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/delete" onsubmit="return confirm('¿Marcar esta campaña como cancelada?');">
                            <?= Csrf::input() ?>
                            <button type="submit" class="btn btn-outline-danger w-100">Cancelar campaña</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($campaign['status'] === 'cancelled'): ?>
                        <div class="alert alert-secondary mb-0">
                            Esta campaña ya está cancelada.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
