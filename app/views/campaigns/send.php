<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Preparar envío</h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Resumen</h2>
            <?php if (!$message): ?>
                <div class="alert alert-warning mb-0">No puedes iniciar el envío hasta guardar el mensaje de la campaña.</div>
            <?php else: ?>
                <p class="mb-2"><strong>Destinatarios activos:</strong> <?= (int) $recipientsCount ?></p>
                <p class="mb-2"><strong>Asunto:</strong> <?= htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><strong>Remitente:</strong> <?= htmlspecialchars($message['from_name'] . ' <' . $message['from_email'] . '>', ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Validación SES</h2>
            <ul class="small mb-3">
                <li>Región: <?= htmlspecialchars((string) ($settings['ses_region'] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'No configurada' ?></li>
                <li>From por defecto: <?= htmlspecialchars((string) ($settings['ses_from_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'No configurado' ?></li>
                <li>Access key: <?= !empty($settings['ses_key']) ? 'Cargada' : 'Falta' ?></li>
                <li>Secret key: <?= !empty($settings['ses_secret']) ? 'Cargada' : 'Falta' ?></li>
            </ul>
            <?php if ($message): ?>
            <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/send/start">
                <?= Csrf::input() ?>
                <button type="submit" class="btn btn-primary w-100">Iniciar envío</button>
            </form>
            <?php endif; ?>
        </div></div>
    </div>
</div>
