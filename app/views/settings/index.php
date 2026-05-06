<?php
$appConfig = require CONFIG_PATH . '/app.php';
$baseUrl = rtrim((string) ($appConfig['base_url'] ?? 'https://mailer.umbrales.org'), '/');
$cronKey = (string) ($settings['scheduled_cron_key'] ?? '');
$cronUrl = $cronKey !== '' ? $baseUrl . '/cron/scheduled-send?key=' . rawurlencode($cronKey) : 'Configura primero scheduled_cron_key en la tabla settings.';
$redirectUri = $baseUrl . '/settings/gmail-oauth/callback';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Configuración</h1>
        <p class="text-muted mb-0">Credenciales globales, cron técnico y preparación segura para respuestas automáticas.</p>
    </div>
</div>

<div class="alert alert-warning">
    Esta sección es exclusiva para administradores. No debe estar disponible para usuarios operativos.
</div>

<div class="card border-0 shadow-sm mb-4">
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

            <hr class="my-4">
            <h2 class="h5 mb-2">Cron Job global</h2>
            <p class="text-muted small mb-3">Un solo Cron Job revisa todas las campañas programadas. Debe configurarse en Hostinger cada 15 minutos.</p>
            <div class="mb-3">
                <label class="form-label">URL global del cron</label>
                <textarea class="form-control code-like" rows="2" readonly><?= htmlspecialchars($cronUrl, ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Comando wget recomendado</label>
                    <textarea class="form-control code-like" rows="3" readonly><?= htmlspecialchars('wget -q -O /dev/null "' . $cronUrl . '"', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comando curl alternativo</label>
                    <textarea class="form-control code-like" rows="3" readonly><?= htmlspecialchars('curl -s "' . $cronUrl . '" > /dev/null 2>&1', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <hr class="my-4">
            <h2 class="h5 mb-2">Detección segura de respuestas — Gmail API / OAuth</h2>
            <div class="alert alert-info mb-3">
                Esta es la opción recomendada para producción. Queda preparada para retomarla después. No usa IMAP ni contraseñas de buzón. Mientras esté desactivada, no altera envíos ni cron.
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select class="form-select" name="reply_detection_enabled">
                        <option value="0" <?= (string) ($settings['reply_detection_enabled'] ?? '0') !== '1' ? 'selected' : '' ?>>Desactivada / pendiente</option>
                        <option value="1" <?= (string) ($settings['reply_detection_enabled'] ?? '0') === '1' ? 'selected' : '' ?>>Activada cuando OAuth quede conectado</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proveedor</label>
                    <input type="text" class="form-control" value="Gmail API OAuth 2.0" readonly>
                    <input type="hidden" name="reply_detection_provider" value="gmail_oauth">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Carpeta / label a revisar</label>
                    <input type="text" class="form-control" name="gmail_oauth_label" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_label'] ?? 'INBOX'), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Buzón que recibe respuestas</label>
                    <input type="email" class="form-control" name="gmail_oauth_mailbox" placeholder="instituto@umbrales.org" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_mailbox'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Redirect URI futuro</label>
                    <input type="text" class="form-control" name="gmail_oauth_redirect_uri" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_redirect_uri'] ?: $redirectUri), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text">Úsalo después al crear el OAuth Client en Google Cloud.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Google OAuth Client ID</label>
                    <input type="text" class="form-control" name="gmail_oauth_client_id" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_client_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Google OAuth Client Secret</label>
                    <input type="password" class="form-control" name="gmail_oauth_client_secret" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_client_secret'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Refresh token futuro</label>
                    <input type="password" class="form-control" name="gmail_oauth_refresh_token" value="<?= htmlspecialchars((string) ($settings['gmail_oauth_refresh_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-text">Este campo queda reservado para la fase OAuth. No lo llenes todavía si aún no conectamos Gmail API.</div>
                </div>
            </div>

            <div class="mt-3"><button type="submit" class="btn btn-primary">Guardar configuración</button></div>
        </form>
    </div>
</div>
