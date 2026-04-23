<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Monitoreo de envío #<?= (int) $session['id'] ?></h1>
        <p class="text-muted mb-0">Campaña: <?= htmlspecialchars($session['campaign_name'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <a href="/campaigns/<?= (int) $session['campaign_id'] ?>" class="btn btn-outline-secondary">Volver a campaña</a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Estado</div><div class="h5 mb-0" id="js-status"><?= htmlspecialchars($session['status'], ENT_QUOTES, 'UTF-8') ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Procesados</div><div class="h5 mb-0"><span id="js-processed"><?= (int) $session['processed_count'] ?></span> / <span id="js-total"><?= (int) $session['total_count'] ?></span></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Enviados</div><div class="h5 mb-0" id="js-success"><?= (int) $session['success_count'] ?></div></div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body"><div class="text-muted small">Fallidos</div><div class="h5 mb-0" id="js-failed"><?= (int) $session['failed_count'] ?></div></div></div></div>
</div>

<div class="card border-0 shadow-sm mb-4"><div class="card-body">
    <div class="progress mb-3" role="progressbar" aria-label="Progreso de envío">
        <div class="progress-bar" id="js-progress-bar" style="width: <?= (int) ($session['total_count'] > 0 ? round(((int) $session['processed_count'] / max(1, (int) $session['total_count'])) * 100) : 0) ?>%"></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary" id="js-start">Procesar siguiente lote</button>
        <button class="btn btn-outline-primary" id="js-auto">Auto procesar</button>
        <button class="btn btn-outline-warning" id="js-pause">Pausar</button>
        <button class="btn btn-outline-success" id="js-resume">Reanudar</button>
    </div>
    <p class="small text-muted mt-3 mb-0">Esta pantalla procesa lotes de <?= (int) $batchSize ?> destinatarios por solicitud para evitar timeouts del hosting.</p>
</div></div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <h2 class="h5">Últimos movimientos</h2>
    <div class="table-responsive">
        <table class="table table-sm align-middle" id="js-items-table">
            <thead><tr><th>ID</th><th>Correo</th><th>Estado</th><th>Error</th><th>Procesado</th></tr></thead>
            <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>#<?= (int) $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($item['error_message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($item['processed_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div></div>

<script>
const csrf = '<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>';
const sessionId = <?= (int) $session['id'] ?>;
const batchSize = <?= (int) $batchSize ?>;
let auto = false;
let busy = false;

function updateUI(payload) {
    const session = payload.session;
    document.getElementById('js-status').textContent = session.status;
    document.getElementById('js-processed').textContent = session.processed_count;
    document.getElementById('js-total').textContent = session.total_count;
    document.getElementById('js-success').textContent = session.success_count;
    document.getElementById('js-failed').textContent = session.failed_count;
    const percent = session.total_count > 0 ? Math.round((session.processed_count / session.total_count) * 100) : 0;
    document.getElementById('js-progress-bar').style.width = percent + '%';
    const tbody = document.querySelector('#js-items-table tbody');
    tbody.innerHTML = '';
    payload.items.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>#${item.id}</td><td>${escapeHtml(item.email)}</td><td>${escapeHtml(item.status)}</td><td>${escapeHtml(item.error_message || '')}</td><td>${escapeHtml(item.processed_at || '—')}</td>`;
        tbody.appendChild(tr);
    });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

async function postAction(url, body = {}) {
    const params = new URLSearchParams();
    params.append('_token', csrf);
    Object.entries(body).forEach(([k, v]) => params.append(k, v));
    const response = await fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    });
    return response.json();
}

async function refreshStatus() {
    const response = await fetch(`/sending/${sessionId}/status`);
    const data = await response.json();
    updateUI(data);
}

async function processStep() {
    if (busy) return;
    busy = true;
    try {
        const data = await postAction(`/sending/${sessionId}/process`, {limit: batchSize});
        if (data.ok) {
            updateUI(data);
            if (auto && data.session.status === 'processing') {
                setTimeout(processStep, 500);
            }
        } else {
            auto = false;
            alert(data.error || 'Error al procesar lote.');
        }
    } catch (e) {
        auto = false;
        alert('Error al comunicarse con el servidor.');
    } finally {
        busy = false;
    }
}

document.getElementById('js-start').addEventListener('click', () => { auto = false; processStep(); });
document.getElementById('js-auto').addEventListener('click', () => { auto = true; processStep(); });
document.getElementById('js-pause').addEventListener('click', async () => { auto = false; await postAction(`/sending/${sessionId}/pause`); refreshStatus(); });
document.getElementById('js-resume').addEventListener('click', async () => { await postAction(`/sending/${sessionId}/resume`); refreshStatus(); });
setInterval(refreshStatus, 5000);
</script>
