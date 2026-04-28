<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Vista previa del mensaje</h1>
        <p class="text-muted mb-0"><?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/campaigns/<?= (int) $campaign['id'] ?>/message" class="btn btn-outline-secondary">Editar mensaje</a>
        <a href="/campaigns/<?= (int) $campaign['id'] ?>" class="btn btn-outline-secondary">Volver</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <div class="row text-sm">
                    <div class="col-md-6">
                        <strong>De:</strong> <?= htmlspecialchars(($message['from_name'] ?? '') . ' <' . ($message['from_email'] ?? '') . '>', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Asunto:</strong> <?= htmlspecialchars($message['subject'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (($campaign['content_mode'] ?? 'text') === 'html' && !empty($message['html_body'])): ?>
                    <iframe
                        srcdoc="<?= htmlspecialchars($message['html_body'], ENT_QUOTES, 'UTF-8') ?>"
                        style="width:100%;height:600px;border:none;"
                        sandbox="allow-same-origin"
                    ></iframe>
                <?php else: ?>
                    <div class="p-4">
                        <pre style="white-space:pre-wrap;font-family:inherit"><?= htmlspecialchars($message['text_body'] ?? '', ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Enviar correo de prueba</h5>
                <p class="text-muted small">Envía este mensaje a un correo interno para verificar cómo se ve antes del envío masivo.</p>
                <div class="mb-3">
                    <label class="form-label">Correo de prueba</label>
                    <input type="email" id="testEmail" class="form-control" placeholder="tu@correo.com">
                </div>
                <button class="btn btn-primary w-100" id="btnSendTest">
                    <span id="btnTestText">Enviar prueba</span>
                </button>
                <div id="testResult" class="mt-3 d-none"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Detalles</h5>
                <table class="table table-sm">
                    <tr><td class="text-muted">Modo</td><td><?= htmlspecialchars($campaign['content_mode'] ?? 'text', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted">Asunto</td><td><?= htmlspecialchars($message['subject'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr><td class="text-muted">Reply-To</td><td><?= htmlspecialchars($message['reply_to'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const csrf = '<?= htmlspecialchars(\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>';
const campaignId = <?= (int) $campaign['id'] ?>;

document.getElementById('btnSendTest').addEventListener('click', async function () {
    const email = document.getElementById('testEmail').value.trim();
    if (!email) {
        alert('Ingresa un correo de prueba.');
        return;
    }

    const btn = this;
    btn.disabled = true;
    document.getElementById('btnTestText').textContent = 'Enviando...';

    const params = new URLSearchParams();
    params.append('_token', csrf);
    params.append('test_email', email);

    try {
        const response = await fetch(`/campaigns/${campaignId}/test`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        });
        const data = await response.json();
        const resultDiv = document.getElementById('testResult');
        resultDiv.classList.remove('d-none', 'alert-success', 'alert-danger');
        resultDiv.classList.add('alert', data.ok ? 'alert-success' : 'alert-danger');
        resultDiv.textContent = data.ok ? (data.message || 'Enviado correctamente.') : (data.error || 'Error al enviar.');
    } catch (e) {
        alert('Error de conexión.');
    } finally {
        btn.disabled = false;
        document.getElementById('btnTestText').textContent = 'Enviar prueba';
    }
});
</script>
