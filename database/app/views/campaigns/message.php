<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Mensaje de campaña</h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="alert alert-info small">
    Etiquetas disponibles: <code>%%firstName%%</code>, <code>%%lastName%%</code>, <code>%%emailAddress%%</code>, <code>%%institution%%</code>, <code>%%country%%</code>, <code>%%segment%%</code>, <code>%%status%%</code>, <code>%%consentDate%%</code>, <code>%%unsubscribeUrl%%</code>
</div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/message">
        <?= Csrf::input() ?>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Asunto</label><input type="text" class="form-control" name="subject" value="<?= htmlspecialchars((string) ($message['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="col-md-4"><label class="form-label">Reply-To</label><input type="email" class="form-control" name="reply_to" value="<?= htmlspecialchars((string) ($message['reply_to'] ?? ($settings['ses_reply_to'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Remitente (nombre)</label><input type="text" class="form-control" name="from_name" value="<?= htmlspecialchars((string) ($message['from_name'] ?? ($settings['ses_from_name'] ?? 'Equipo Umbrales')), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Remitente (correo)</label><input type="email" class="form-control" name="from_email" value="<?= htmlspecialchars((string) ($message['from_email'] ?? ($settings['ses_from_email'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="col-lg-6"><label class="form-label">HTML</label><textarea class="form-control code-like" name="html_body" required><?= htmlspecialchars((string) ($message['html_body'] ?? '<p>Hola %%firstName%%,</p><p>Este es un mensaje de prueba.</p><p><a href="%%unsubscribeUrl%%">Cancelar suscripción</a></p>'), ENT_QUOTES, 'UTF-8') ?></textarea></div>
            <div class="col-lg-6"><label class="form-label">Texto plano</label><textarea class="form-control code-like" name="text_body" required><?= htmlspecialchars((string) ($message['text_body'] ?? "Hola %%firstName%%,

Este es un mensaje de prueba.

Cancelar suscripción: %%unsubscribeUrl%%"), ENT_QUOTES, 'UTF-8') ?></textarea></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-primary">Guardar mensaje</button></div>
    </form>
</div></div>
