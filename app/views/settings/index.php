<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Configuración</h1>
        <p class="text-muted mb-0">Credenciales globales, remitente por defecto y automatización de envíos programados.</p>
    </div>
</div>

<div class="alert alert-info border-0 shadow-sm mb-4">
    Esta sección es exclusiva para administradores. Los usuarios operativos pueden gestionar campañas, destinatarios, envíos y secuencias, pero no pueden modificar configuración global ni administrar usuarios.
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Amazon SES</h2>
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
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-2">Cron global de envíos programados</h2>
                <p class="text-muted">Este Cron Job es único y sirve para todas las campañas. Revisa cada 15 minutos si hay mensajes vencidos y solo envía los que correspondan.</p>

                <label class="form-label">URL del cron</label>
                <textarea class="form-control code-like mb-3" rows="3" readonly><?= htmlspecialchars((string) ($cronUrl ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                <label class="form-label">Comando recomendado para Hostinger</label>
                <textarea class="form-control code-like mb-3" rows="3" readonly><?= htmlspecialchars((string) ($cronWgetCommand ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                <label class="form-label">Comando alternativo con curl</label>
                <textarea class="form-control code-like mb-3" rows="3" readonly><?= htmlspecialchars((string) ($cronCurlCommand ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                <div class="alert alert-secondary mb-0">
                    Frecuencia recomendada en Hostinger: <strong>cada 15 minutos</strong>.<br>
                    Expresión cron equivalente: <code>*/15 * * * *</code>.
                </div>
            </div>
        </div>
    </div>
</div>
