<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Historial de campañas</h1>
        <p class="text-muted mb-0">
            <?= htmlspecialchars(trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
            · <?= htmlspecialchars($recipient['email'], ENT_QUOTES, 'UTF-8') ?>
        </p>
    </div>
    <a href="/recipients" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Campaña</th>
                    <th>Asignado</th>
                    <th>Estado envío</th>
                    <th>Fecha envío</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td>
                            <a href="/campaigns/<?= (int) $row['campaign_id'] ?>">
                                <?= htmlspecialchars($row['campaign_name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td class="small"><?= htmlspecialchars((string)($row['assigned_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php
                            $status = $row['send_status'] ?? 'pendiente';
                            $badge = match($status) {
                                'sent' => 'success',
                                'failed' => 'danger',
                                'pending' => 'secondary',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td class="small"><?= htmlspecialchars((string)($row['processed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="small text-danger"><?= htmlspecialchars((string)($row['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">Este destinatario no ha sido asignado a ninguna campaña.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>