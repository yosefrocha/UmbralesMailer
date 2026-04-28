<?php
$summary = $summary ?? [];
$deliveries = $deliveries ?? [];
$nowValue = (new DateTimeImmutable('+5 minutes'))->format('Y-m-d\TH:i');
$statusLabels = [
    'pending' => 'Pendiente',
    'sent' => 'Enviado',
    'failed' => 'Fallido',
    'skipped' => 'Detenido',
    'cancelled' => 'Cancelado',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h1 class="h3 mb-1">Secuencia programada</h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars((string) $campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/send" class="btn btn-outline-primary">Envío inmediato</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string) $success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Esta programación envía <strong>1 mensaje por destinatario cada 2 días</strong>, hasta cumplir la cuota definida. Se detiene si la campaña se cancela, si el destinatario se desuscribe, si se elimina de la campaña, si se marca que respondió o si queda inactivo.
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Total programados</div><div class="metric-value"><?= (int) ($summary['total'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Pendientes</div><div class="metric-value"><?= (int) ($summary['pending'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Vencidos hoy</div><div class="metric-value"><?= (int) ($summary['due'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Enviados</div><div class="metric-value"><?= (int) ($summary['sent'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Respondieron</div><div class="metric-value"><?= (int) ($summary['responded'] ?? 0) ?></div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card metric-card h-100"><div class="card-body"><div class="metric-label">Detenidos</div><div class="metric-value"><?= (int) ($summary['skipped'] ?? 0) ?></div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Configurar secuencia</h2>
                <p class="text-muted small">El sistema programa una secuencia por cada destinatario activo: mensaje 1, espera 2 días, mensaje 2, espera 2 días, y así hasta completar la cuota.</p>

                <?php if (!$message): ?>
                    <div class="alert alert-warning mb-0">Primero guarda el mensaje de la campaña antes de programar el envío.</div>
                <?php elseif ((int) $activeRecipients <= 0): ?>
                    <div class="alert alert-warning mb-0">Primero asigna destinatarios activos a la campaña.</div>
                <?php else: ?>
                    <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule" class="row g-3">
                        <?= Csrf::input() ?>
                        <div class="col-12">
                            <label class="form-label">Fecha y hora del primer mensaje</label>
                            <input type="datetime-local" name="start_at" class="form-control" value="<?= htmlspecialchars($nowValue, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mensajes por destinatario</label>
                            <input type="number" name="quota_per_recipient" class="form-control" min="1" max="60" value="10" required>
                            <div class="form-text">Cuota recomendada: 10 mensajes por destinatario.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Días entre mensajes</label>
                            <input type="number" name="interval_days" class="form-control" min="1" max="365" value="2" required>
                            <div class="form-text">Para “un día sí y un día no”, usa 2.</div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Guardar secuencia</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Ejecución automática</h2>
                <p class="text-muted small">Configura este enlace en Hostinger como Cron Job. El cron puede correr cada 15 minutos; solo enviará los mensajes que ya vencieron.</p>
                <label class="form-label">URL del cron</label>
                <textarea class="form-control small" rows="3" readonly><?= htmlspecialchars((string) $cronUrl, ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="form-text mb-3">No cambia la regla de 2 días. Solo revisa si ya toca enviar.</div>

                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/process-now" class="d-inline">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="limit" value="50">
                    <button type="submit" class="btn btn-outline-success">Procesar vencidos ahora</button>
                </form>

                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/cancel" class="d-inline" onsubmit="return confirm('¿Cancelar todos los envíos pendientes de esta secuencia?');">
                    <?= Csrf::input() ?>
                    <button type="submit" class="btn btn-outline-danger">Cancelar pendientes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">Detalle de secuencia</h2>
                <p class="text-muted small mb-0">Se muestran hasta 500 registros programados.</p>
            </div>
            <div class="small text-muted">
                Próximo pendiente: <?= htmlspecialchars((string) ($summary['next_scheduled_for'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha programada</th>
                        <th>Correo</th>
                        <th>Nombre</th>
                        <th>Institución</th>
                        <th>Estado</th>
                        <th>Respondió</th>
                        <th>Enviado</th>
                        <th>Error / motivo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($deliveries)): ?>
                    <?php foreach ($deliveries as $row): ?>
                        <tr>
                            <td><?= (int) ($row['send_number'] ?? 1) ?></td>
                            <td><?= htmlspecialchars((string) $row['scheduled_for'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($statusLabels[(string) $row['status']] ?? (string) $row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= !empty($row['responded_at']) ? htmlspecialchars((string) $row['responded_at'], ENT_QUOTES, 'UTF-8') : 'No' ?></td>
                            <td><?= htmlspecialchars((string) ($row['sent_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="small text-danger"><?= htmlspecialchars((string) ($row['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (empty($row['responded_at'])): ?>
                                    <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/responded/<?= (int) $row['recipient_id'] ?>" onsubmit="return confirm('¿Marcar que este destinatario respondió y detener sus pendientes?');">
                                        <?= Csrf::input() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Marcar respondió</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/responded/<?= (int) $row['recipient_id'] ?>/clear" onsubmit="return confirm('¿Quitar la marca de respuesta?');">
                                        <?= Csrf::input() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Quitar marca</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Todavía no hay entregas programadas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
