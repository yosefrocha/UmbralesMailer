<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Configuración</h1>
        <p class="text-muted mb-0">Credenciales y remitente por defecto de Amazon SES.</p>
    </div>
</div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <form method="post" action="/settings/save">
        <?= Csrf::input() ?>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Región SES</label><input type="text" class="form-control" name="ses_region" value="<?= htmlspecialchars((string) ($settings['ses_region'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="col-md-4"><label class="form-label">From email</label><input type="email" class="form-control" name="ses_from_email" value="<?= htmlspecialchars((string) ($settings['ses_from_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required></div>
            <div class="col-md-4"><label class="form-label">From name</label><input type="text" class="form-control" name="ses_from_name" value="<?= htmlspecialchars((string) ($settings['ses_from_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Access Key ID</label><input type="text" class="form-control" name="ses_key" value="<?= htmlspecialchars((string) ($settings['ses_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Secret Access Key</label><input type="password" class="form-control" name="ses_secret" value="<?= htmlspecialchars((string) ($settings['ses_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Reply-To</label><input type="email" class="form-control" name="ses_reply_to" value="<?= htmlspecialchars((string) ($settings['ses_reply_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            <div class="col-md-6"><label class="form-label">Configuration Set</label><input type="text" class="form-control" name="ses_configuration_set" value="<?= htmlspecialchars((string) ($settings['ses_configuration_set'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-primary">Guardar configuración</button></div>
    </form>
</div></div>
