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
                <p class="mb-2"><strong>Remitente:</strong> <?= htmlspecialchars($message['from_name'] . ' <' . $message['from_email'] . '>', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="mb-0"><strong>Tipo:</strong> <?= ($campaign['content_mode'] ?? 'text') === 'html' ? 'HTML' : 'Texto plano' ?></p>
            <?php endif; ?>
        </div></div>

        <?php if ($message): ?>
        <div class="card border-0 shadow-sm mt-3"><div class="card-body">
            <h2 class="h5">Vista previa del mensaje</h2>
            <?php if (($campaign['content_mode'] ?? 'text') === 'html'): ?>
                <div class="border rounded p-3 bg-white" style="max-height:400px;overflow-y:auto;">
                    <?= $message['html_body'] ?>
                </div>
            <?php else: ?>
                <pre class="border rounded p-3 bg-light" style="max-height:400px;overflow-y:auto;white-space:pre-wrap;"><?= htmlspecialchars((string)($message['text_body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></pre>
            <?php endif; ?>
        </div></div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body">
            <h2 class="h5">Validación SES</h2>
            <ul class="small mb-3">
                <li>Región: <?= htmlspecialchars((string)($settings['ses_region'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '<span class="text-danger">No configurada</span>' ?></li>
                <li>From: <?= htmlspecialchars((string)($settings['ses_from_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '<span class="text-danger">No configurado</span>' ?></li>
                <li>Access key: <?= !empty($settings['ses_key']) ? '<span class="text-success">Cargada</span>' : '<span class="text-danger">Falta</span>' ?></li>
                <li>Secret key: <?= !empty($settings['ses_secret']) ? '<span class="text-success">Cargada</span>' : '<span class="text-danger">Falta</span>' ?></li>
            </ul>
            <?php if ($message): ?>
            <form method="post" action="/campaigns/<?= (int) $campaign['id'] ?>/send/start">
                <?= Csrf::input() ?>
                <button type="submit" class="btn btn-primary w-100">Iniciar envío</button>
            </form>
            <?php endif; ?>
        </div></div>

        <?php if ($message): ?>
        <div class="card border-0 shadow-sm"><div class="card-body">
            <h2 class="h5">Envío de prueba</h2>
            <p class="small text-muted">Envía el mensaje a un correo interno antes de lanzar la campaña.</p>
            <div class="input-group mb-2">
                <input type="email" class="form-control" id="test-email-input"
                       placeholder="correo@ejemplo.com"
                       value="<?= htmlspecialchars((string)($settings['ses_reply_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-outline-secondary" id="btn-send-test">Enviar prueba</button>
            </div>
            <div id="test-result" class="small"></div>
        </div></div>
        <?php endif; ?>
    </div>
</div>

<?php if ($message): ?>
<script>
const csrf = '<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>';
const campaignId = <?= (int) $campaign['id'] ?>;

document.getElementById('btn-send-test').addEventListener('click', async function () {
    const email = document.getElementById('test-email-input').value.trim();
    const result = document.getElementById('test-result');

    if (!email) {
        result.innerHTML = '<span class="text-danger">Ingresa un correo válido.</span>';
        return;
    }

    this.disabled = true;
    this.textContent = 'Enviando...';
    result.innerHTML = '';

    try {
        const params = new URLSearchParams();
        params.append('_token', csrf);
        params.append('test_email', email);

        const r = await fetch(`/campaigns/${campaignId}/test`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        });

        const data = await r.json();

        if (data.ok) {
            result.innerHTML = `<span class="text-success">✓ ${data.message}</span>`;
        } else {
            result.innerHTML = `<span class="text-danger">✗ ${data.error}</span>`;
        }
    } catch (e) {
        result.innerHTML = '<span class="text-danger">Error al comunicarse con el servidor.</span>';
    } finally {
        this.disabled = false;
        this.textContent = 'Enviar prueba';
    }
});
</script>
<?php endif; ?>