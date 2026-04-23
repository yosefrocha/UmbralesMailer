<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/campaigns" class="btn btn-outline-secondary">Volver</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/edit" class="btn btn-outline-primary">Editar</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-primary">Mensaje</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/recipients" class="btn btn-outline-primary">Destinatarios</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Estado</div>
                <div class="h4 mb-0">
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
                    ?>
                    <?= htmlspecialchars($labels[$status] ?? $status, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Destinatarios activos</div>
                <div class="h4 mb-0"><?= (int) $activeRecipients ?></div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="text-muted small">Última sesión</div>
                <div class="h6 mb-0">
                    <?= !empty($latestSession) ? '#' . (int) $latestSession['id'] . ' · ' . htmlspecialchars((string) $latestSession['status'], ENT_QUOTES, 'UTF-8') : 'Sin sesiones' ?>
                </div>
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