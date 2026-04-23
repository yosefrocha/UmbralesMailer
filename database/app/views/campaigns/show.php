<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted mb-0"><?= htmlspecialchars((string) ($campaign['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/edit" class="btn btn-outline-primary">Editar</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-primary">Mensaje</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Estado</div><div class="h4 mb-0"><?= htmlspecialchars($campaign['status'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div>
    <div class="col-lg-4"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Destinatarios activos</div><div class="h4 mb-0"><?= (int) $activeRecipients ?></div></div></div></div>
    <div class="col-lg-4"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Última sesión</div><div class="h6 mb-0"><?= !empty($latestSession) ? '#' . (int) $latestSession['id'] . ' · ' . htmlspecialchars($latestSession['status'], ENT_QUOTES, 'UTF-8') : 'Sin sesiones' ?></div></div></div></div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Mensaje configurado</h2>
            <?php if ($message): ?>
                <p class="mb-1"><strong>Asunto:</strong> <?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-1"><strong>Remitente:</strong> <?= htmlspecialchars($message['from_name'] . ' <' . $message['from_email'] . '>', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><strong>Texto plano:</strong> <?= strlen((string) $message['text_body']) ?> caracteres</p>
            <?php else: ?>
                <p class="text-muted">Aún no has configurado el mensaje de esta campaña.</p>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Acciones</h2>
            <div class="d-grid gap-2">
                <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-outline-primary">Editar mensaje</a>
                <a href="/campaigns/<?= (int) $campaign['id'] ?>/send" class="btn btn-primary <?= $message ? '' : 'disabled' ?>">Preparar envío</a>
                <?php if (Auth::isAdmin()): ?>
                <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/delete" onsubmit="return confirm('¿Marcar esta campaña como cancelada?');">
                    <?= Csrf::input() ?>
                    <button type="submit" class="btn btn-outline-danger w-100">Cancelar campaña</button>
                </form>
                <?php endif; ?>
            </div>
        </div></div>
    </div>
</div>
