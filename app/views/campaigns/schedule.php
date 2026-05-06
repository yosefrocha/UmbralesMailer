<?php
$nowValue = date('Y-m-d\TH:i');
$summary = $summary ?? [];
$recipientRows = $recipientRows ?? [];
$recipientRowsTotal = (int) ($recipientRowsTotal ?? count($recipientRows));
$isAdmin = Auth::isAdmin();
?>
<div class="d-flex justify-content-between align-items-center mb-4 page-header">
    <div>
        <h1 class="h3 mb-1">Secuencia programada</h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2 page-actions">
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
    La secuencia envía <strong>1 mensaje por destinatario cada 2 días</strong> hasta completar la cuota. La detección automática de respuestas queda preparada para una fase posterior con <strong>Gmail API / OAuth</strong>, no con IMAP.
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
    <div class="col-lg-<?= $isAdmin ? '6' : '12' ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Configurar secuencia</h2>
                <p class="text-muted small">Programa una secuencia por destinatario activo: mensaje 1, espera 2 días, mensaje 2, y así hasta completar la cuota.</p>

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

    <?php if ($isAdmin): ?>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5">Herramientas administrativas</h2>
                <p class="text-muted small">La URL del cron y sus comandos están centralizados en Configuración. Esta pantalla solo permite acciones operativas de la campaña.</p>
                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/process-now" class="d-inline" onsubmit="return confirm('Esta acción procesará únicamente mensajes vencidos. No envía mensajes futuros. ¿Continuar?');">
                    <?= Csrf::input() ?>
                    <input type="hidden" name="limit" value="50">
                    <button type="submit" class="btn btn-outline-success">Procesar vencidos ahora</button>
                </form>
                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/schedule/cancel" class="d-inline" onsubmit="return confirm('¿Cancelar todos los envíos pendientes de esta secuencia?');">
                    <?= Csrf::input() ?>
                    <button type="submit" class="btn btn-outline-danger">Cancelar pendientes</button>
                </form>
                <hr>
                <div class="small text-muted">
                    Respuestas automáticas: preparadas para Gmail API / OAuth en Configuración. No hay lectura IMAP activa.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">Resumen por destinatario</h2>
                <p class="text-muted small mb-0">Vista optimizada para volumen: una fila por destinatario. Mostrando hasta 100 de <?= $recipientRowsTotal ?>.</p>
            </div>
            <div class="small text-muted">
                Próximo pendiente: <?= htmlspecialchars((string) ($summary['next_scheduled_for'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Correo</th>
                        <th>Nombre</th>
                        <th>Institución</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                        <th>Próximo envío</th>
                        <th>Último enviado</th>
                        <th>Respuesta</th>
                        <th>Motivo / error</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($recipientRows)): ?>
                    <?php foreach ($recipientRows as $row): ?>
                        <?php
                        $sent = (int) ($row['sent_count'] ?? 0);
                        $total = max(0, (int) ($row['total_scheduled'] ?? 0));
                        $failed = (int) ($row['failed_count'] ?? 0);
                        $pending = (int) ($row['pending_count'] ?? 0);
                        $responded = !empty($row['responded_at']);
                        $unsubscribed = !empty($row['unsubscribed_at']);
                        $statusLabel = 'Activo';
                        if ($responded) {
                            $statusLabel = 'Respondió';
                        } elseif ($unsubscribed) {
                            $statusLabel = 'Desuscrito';
                        } elseif ((string) ($row['campaign_recipient_status'] ?? '') === 'excluded') {
                            $statusLabel = 'Eliminado';
                        } elseif ($failed > 0) {
                            $statusLabel = 'Con fallos';
                        } elseif ($pending > 0) {
                            $statusLabel = 'Con pendientes';
                        } elseif ($total > 0 && $sent >= $total) {
                            $statusLabel = 'Completado';
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['institution'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $sent ?> / <?= $total ?></td>
                            <td><?= htmlspecialchars((string) ($row['next_scheduled_for'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['last_sent_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $responded ? htmlspecialchars((string) $row['responded_at'], ENT_QUOTES, 'UTF-8') : 'No detectada' ?></td>
                            <td class="small text-danger"><?= htmlspecialchars((string) ($row['stop_reason'] ?? $row['last_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Todavía no hay destinatarios o entregas programadas.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
